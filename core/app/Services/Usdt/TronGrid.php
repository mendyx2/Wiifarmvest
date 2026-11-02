<?php

namespace App\Services\Usdt;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Reads USDT (TRC20) payments from the Tron blockchain through TronGrid,
 * the public API of the Tron network. It only reads; it never holds keys.
 *
 * transaction() is the one source of truth before money moves: it asks the
 * *solidity* node, which only answers once a transaction is final (about a
 * minute after it is sent), and reads the USDT "Transfer" events of the
 * official contract (config usdt.networks.*.contract) out of the receipt.
 * incoming() only discovers transaction IDs; every one it finds is checked
 * again with transaction() before it can be credited.
 */
class TronGrid
{
    /** keccak256("Transfer(address,address,uint256)"), the ERC-20/TRC-20 transfer event */
    public const TRANSFER_TOPIC = 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

    private string $api;
    private string $contract;
    private string $contractHex;

    public function __construct(private ?string $apiKey = null, ?string $network = null)
    {
        $net = config('usdt.networks.' . ($network ?? config('usdt.network')));
        if (!$net) {
            throw new \RuntimeException('Unknown USDT_TRON_NETWORK.');
        }
        $this->api         = rtrim($net['api'], '/');
        $this->contract    = $net['contract'];
        $this->contractHex = substr((string) TronAddress::toHex($net['contract']), 2);
    }

    public function contract(): string
    {
        return $this->contract;
    }

    /**
     * One transaction, read from the final (solidity) state of the chain.
     *
     * @return array{state: string, time_ms: ?int, transfers: list<array{from: ?string, to: string, units: int}>}
     *   state: "confirmed" (final), "pending" (seen, not final yet),
     *   "missing" (not on the chain), "failed" (on the chain but reverted).
     *   transfers: the official USDT transfers in it (empty if none).
     * @throws TronGridUnavailable when TronGrid cannot be asked or answers nonsense
     */
    public function transaction(string $txid): array
    {
        $txid = strtolower($txid);
        $info = $this->post('/walletsolidity/gettransactioninfobyid', ['value' => $txid]);

        if (empty($info['id'])) {
            // Not final yet, or not there at all: the full node knows the difference
            $pending = $this->post('/wallet/gettransactioninfobyid', ['value' => $txid]);
            return ['state' => empty($pending['id']) ? 'missing' : 'pending', 'time_ms' => null, 'transfers' => []];
        }
        if (strtolower((string) $info['id']) !== $txid) {
            throw new TronGridUnavailable('TronGrid answered for a different transaction.');
        }

        $time = isset($info['blockTimeStamp']) ? (int) $info['blockTimeStamp'] : null;

        // A reverted contract call moves no tokens, whatever it tried
        if (($info['result'] ?? null) === 'FAILED' || (isset($info['receipt']['result']) && $info['receipt']['result'] !== 'SUCCESS')) {
            return ['state' => 'failed', 'time_ms' => $time, 'transfers' => []];
        }

        return ['state' => 'confirmed', 'time_ms' => $time, 'transfers' => $this->usdtTransfers((array) ($info['log'] ?? []))];
    }

    /**
     * The official USDT "Transfer" events in a receipt's logs.
     *
     * @return list<array{from: ?string, to: string, units: int}>
     */
    public function usdtTransfers(array $logs): array
    {
        $out = [];
        foreach ($logs as $log) {
            $address = strtolower((string) ($log['address'] ?? ''));
            $address = strlen($address) === 42 && str_starts_with($address, '41') ? substr($address, 2) : $address;
            $topics  = array_map(fn ($t) => strtolower((string) $t), (array) ($log['topics'] ?? []));

            if ($address !== $this->contractHex || count($topics) !== 3 || $topics[0] !== self::TRANSFER_TOPIC) {
                continue;
            }

            $to    = TronAddress::fromHex($topics[2]);
            $units = self::units((string) ($log['data'] ?? ''));
            if ($to === null || $units === null) {
                continue;
            }

            $out[] = ['from' => TronAddress::fromHex($topics[1]), 'to' => $to, 'units' => $units];
        }

        return $out;
    }

    /**
     * Confirmed USDT payments into $address since $sinceMs (oldest first).
     * Used to find payments members did not report; never trusted alone.
     *
     * @return list<array{txid: string, units: int, time_ms: int}>
     * @throws TronGridUnavailable
     */
    public function incoming(string $address, int $sinceMs, int $maxPages = 5): array
    {
        $out    = [];
        $params = [
            'only_to'          => 'true',
            'only_confirmed'   => 'true',
            'contract_address' => $this->contract,
            'min_timestamp'    => $sinceMs,
            'order_by'         => 'block_timestamp,asc',
            'limit'            => 200,
        ];

        for ($page = 0; $page < $maxPages; $page++) {
            $body = $this->get('/v1/accounts/' . $address . '/transactions/trc20', $params);
            if (($body['success'] ?? false) !== true || !is_array($body['data'] ?? null)) {
                throw new TronGridUnavailable('TronGrid did not list the transfers.');
            }

            foreach ($body['data'] as $row) {
                $txid  = strtolower((string) ($row['transaction_id'] ?? ''));
                $value = (string) ($row['value'] ?? '');
                if (!preg_match('/^[0-9a-f]{64}$/', $txid) || !ctype_digit($value) || strlen($value) > 15
                    || ($row['to'] ?? null) !== $address || ($row['token_info']['address'] ?? null) !== $this->contract) {
                    continue;
                }
                $out[] = ['txid' => $txid, 'units' => (int) $value, 'time_ms' => (int) ($row['block_timestamp'] ?? 0)];
            }

            $fingerprint = $body['meta']['fingerprint'] ?? null;
            if (!$fingerprint || count($body['data']) < $params['limit']) {
                break;
            }
            $params['fingerprint'] = $fingerprint;
        }

        return $out;
    }

    /** A 32-byte hex amount → whole units, or null when it is not a sane amount. */
    public static function units(string $hex): ?int
    {
        $hex = strtolower($hex);
        if (!preg_match('/^[0-9a-f]{1,64}$/', $hex)) {
            return null;
        }
        $hex = ltrim($hex, '0');
        if ($hex === '') {
            return 0;
        }
        // 15 hex digits ≈ 1.15e18 units ≈ 1.15 trillion USDT: anything longer is not a real payment
        return strlen($hex) > 15 ? null : hexdec($hex);
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout(10)->connectTimeout(5)->acceptJson()->retry(2, 300, throw: false);

        return $this->apiKey ? $client->withHeaders(['TRON-PRO-API-KEY' => $this->apiKey]) : $client;
    }

    private function post(string $path, array $body): array
    {
        try {
            $response = $this->client()->asJson()->post($this->api . $path, $body);
        } catch (ConnectionException $e) {
            throw new TronGridUnavailable('TronGrid could not be reached.', 0, $e);
        }
        if (!$response->successful() || !is_array($response->json())) {
            throw new TronGridUnavailable('TronGrid answered ' . $response->status() . '.');
        }

        return $response->json();
    }

    private function get(string $path, array $query): array
    {
        try {
            $response = $this->client()->get($this->api . $path, $query);
        } catch (ConnectionException $e) {
            throw new TronGridUnavailable('TronGrid could not be reached.', 0, $e);
        }
        if (!$response->successful() || !is_array($response->json())) {
            throw new TronGridUnavailable('TronGrid answered ' . $response->status() . '.');
        }

        return $response->json();
    }
}
