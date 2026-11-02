<?php

namespace Tests\Unit\Usdt;

use App\Services\Usdt\TronGrid;
use App\Services\Usdt\TronGridUnavailable;
use App\Services\Usdt\UsdtPayments;
use Illuminate\Support\Facades\Http;
use Tests\Unit\Usdt\UsdtTestCase;

/**
 * TronGrid's replies here follow the shapes of /walletsolidity/gettransactioninfobyid
 * and /v1/accounts/{address}/transactions/trc20. A live check against the
 * real network is still required before launch (notes Step 122).
 */
class TronGridTest extends UsdtTestCase
{
    private const TXID   = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
    private const OURS   = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // any valid address works as "ours" in these tests
    private const OURS_H = 'a614f803b6fd780986a42c78ec9c7f77e6ded13c';
    private const FROM_H = '1111111111111111111111111111111111111111';
    private const USDT_H = 'a614f803b6fd780986a42c78ec9c7f77e6ded13c';

    private static function log(string $contractHex, string $toHex, int $units): array
    {
        return [
            'address' => $contractHex,
            'topics'  => [TronGrid::TRANSFER_TOPIC, str_repeat('0', 24) . self::FROM_H, str_repeat('0', 24) . $toHex],
            'data'    => str_pad(dechex($units), 64, '0', STR_PAD_LEFT),
        ];
    }

    private static function info(array $logs, array $extra = []): array
    {
        return array_merge(['id' => self::TXID, 'blockNumber' => 66000000, 'blockTimeStamp' => 1_793_000_000_000, 'contract_address' => '41' . self::USDT_H, 'receipt' => ['result' => 'SUCCESS'], 'log' => $logs], $extra);
    }

    public function test_amounts_from_hex(): void
    {
        $this->assertSame(64_370_000, TronGrid::units(str_pad(dechex(64_370_000), 64, '0', STR_PAD_LEFT)));
        $this->assertSame(0, TronGrid::units(str_repeat('0', 64)));
        $this->assertNull(TronGrid::units(str_repeat('f', 64)), 'an absurd amount is refused, not wrapped');
        $this->assertNull(TronGrid::units('zz'));
    }

    public function test_reads_an_official_usdt_transfer(): void
    {
        Http::fake(['*/walletsolidity/gettransactioninfobyid' => Http::response(self::info([self::log(self::USDT_H, self::OURS_H, 64_370_000)]))]);

        $tx = (new TronGrid())->transaction(strtoupper(self::TXID));

        $this->assertSame('confirmed', $tx['state']);
        $this->assertSame(1_793_000_000_000, $tx['time_ms']);
        $this->assertSame([['from' => 'TBXSw8fM4jpQkGc6zZjsVABFpVN7UvXPdV', 'to' => self::OURS, 'units' => 64_370_000]], $tx['transfers']);
    }

    public function test_ignores_other_tokens_and_events(): void
    {
        $fake  = self::log('2222222222222222222222222222222222222222', self::OURS_H, 64_370_000);       // a token calling itself USDT
        $other = self::log(self::USDT_H, self::OURS_H, 5) + [];
        $other['topics'][0] = str_repeat('a', 64);                                                      // not a Transfer event
        Http::fake(['*/walletsolidity/gettransactioninfobyid' => Http::response(self::info([$fake, $other]))]);

        $this->assertSame([], (new TronGrid())->transaction(self::TXID)['transfers']);
    }

    public function test_a_reverted_transaction_moves_nothing(): void
    {
        Http::fake(['*/walletsolidity/gettransactioninfobyid' => Http::response(self::info([self::log(self::USDT_H, self::OURS_H, 1)], ['receipt' => ['result' => 'REVERT'], 'result' => 'FAILED']))]);

        $tx = (new TronGrid())->transaction(self::TXID);
        $this->assertSame('failed', $tx['state']);
        $this->assertSame([], $tx['transfers']);
    }

    public function test_not_final_yet_or_missing(): void
    {
        Http::fake([
            '*/walletsolidity/gettransactioninfobyid' => Http::response([]),
            '*/wallet/gettransactioninfobyid'         => Http::sequence()->push(['id' => self::TXID, 'blockNumber' => 1])->push([]),
        ]);

        $this->assertSame('pending', (new TronGrid())->transaction(self::TXID)['state']);
        $this->assertSame('missing', (new TronGrid())->transaction(self::TXID)['state']);
    }

    public function test_an_answer_for_another_transaction_is_not_trusted(): void
    {
        Http::fake(['*/walletsolidity/gettransactioninfobyid' => Http::response(self::info([], ['id' => str_repeat('b', 64)]))]);

        $this->expectException(TronGridUnavailable::class);
        (new TronGrid())->transaction(self::TXID);
    }

    public function test_errors_are_unavailable_not_answers(): void
    {
        Http::fake(['*' => Http::response('rate limited', 429)]);

        $this->expectException(TronGridUnavailable::class);
        (new TronGrid())->transaction(self::TXID);
    }

    public function test_sends_the_api_key(): void
    {
        Http::fake(['*' => Http::response([])]);
        (new TronGrid('key-123'))->transaction(self::TXID);

        Http::assertSent(fn ($request) => $request->hasHeader('TRON-PRO-API-KEY', 'key-123'));
    }

    public function test_incoming_keeps_only_official_usdt_to_us(): void
    {
        $row = fn (array $over) => array_merge(['transaction_id' => self::TXID, 'token_info' => ['address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 'symbol' => 'USDT', 'decimals' => 6], 'block_timestamp' => 1_793_000_000_000, 'from' => 'TXfromXX', 'to' => self::OURS, 'type' => 'Transfer', 'value' => '64370000'], $over);
        Http::fake(['*/v1/accounts/*' => Http::response(['success' => true, 'data' => [
            $row([]),
            $row(['transaction_id' => str_repeat('c', 64), 'token_info' => ['address' => 'TFakeTokenAddressXXXXXXXXXXXXXXXXX']]),
            $row(['transaction_id' => str_repeat('d', 64), 'to' => 'TSomeoneElseXXXXXXXXXXXXXXXXXXXXXX']),
            $row(['transaction_id' => 'not-a-txid']),
        ], 'meta' => ['page_size' => 4]])]);

        $this->assertSame([['txid' => self::TXID, 'units' => 64_370_000, 'time_ms' => 1_793_000_000_000]], (new TronGrid())->incoming(self::OURS, 0));
    }

    public function test_formats_units(): void
    {
        $this->assertSame('64.37', UsdtPayments::format(64_370_000));
        $this->assertSame('1.234567', UsdtPayments::format(1_234_567));
        $this->assertSame('0.01', UsdtPayments::format(10_000));
        $this->assertSame('100.00', UsdtPayments::format(100_000_000));
    }
}
