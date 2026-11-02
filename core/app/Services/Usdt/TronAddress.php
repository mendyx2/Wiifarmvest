<?php

namespace App\Services\Usdt;

/**
 * Tron addresses: the "T..." form people copy (Base58Check) and the hex form
 * the blockchain uses inside transactions ("41" + 20 bytes).
 *
 * Pure PHP (no bcmath/gmp needed). Base58Check carries a 4-byte checksum,
 * so a mistyped address is refused rather than accepted as someone else's.
 */
final class TronAddress
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /** True for a well-formed mainnet/testnet Tron address ("T" + 33 characters, valid checksum). */
    public static function isValid(?string $address): bool
    {
        return self::toHex((string) $address) !== null;
    }

    /** "T..." → "41" + 40 hex characters (lower case), or null when not a valid Tron address. */
    public static function toHex(string $address): ?string
    {
        if (!preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $address)) {
            return null;
        }

        $bytes = self::base58Decode($address);
        if ($bytes === null || strlen($bytes) !== 25 || $bytes[0] !== "\x41") {
            return null;
        }

        $payload  = substr($bytes, 0, 21);
        $checksum = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        if (!hash_equals($checksum, substr($bytes, 21, 4))) {
            return null;
        }

        return bin2hex($payload);
    }

    /**
     * Hex → "T...". Accepts "41" + 40 hex, or the bare 40 hex an event log
     * carries (a 64-character topic is cut to its last 40). Null if malformed.
     */
    public static function fromHex(string $hex): ?string
    {
        $hex = strtolower($hex);
        if (preg_match('/^[0-9a-f]{64}$/', $hex)) {
            $hex = substr($hex, -40);
        }
        if (preg_match('/^[0-9a-f]{40}$/', $hex)) {
            $hex = '41' . $hex;
        }
        if (!preg_match('/^41[0-9a-f]{40}$/', $hex)) {
            return null;
        }

        $payload  = hex2bin($hex);
        $checksum = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);

        return self::base58Encode($payload . $checksum);
    }

    private static function base58Decode(string $input): ?string
    {
        $bytes = [0]; // little-endian base-256 digits
        foreach (str_split($input) as $char) {
            $value = strpos(self::ALPHABET, $char);
            if ($value === false) {
                return null;
            }
            $carry = $value;
            foreach ($bytes as $i => $byte) {
                $carry     += $byte * 58;
                $bytes[$i] = $carry & 0xff;
                $carry   >>= 8;
            }
            while ($carry > 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }

        // Leading "1"s are leading zero bytes
        $zeros = strspn($input, '1');
        $out   = implode('', array_map('chr', array_reverse($bytes)));
        $out   = ltrim($out, "\0");

        return str_repeat("\0", $zeros) . $out;
    }

    private static function base58Encode(string $bytes): string
    {
        $digits = [0]; // little-endian base-58 digits
        foreach (str_split($bytes) as $char) {
            $carry = ord($char);
            foreach ($digits as $i => $digit) {
                $carry      += $digit << 8;
                $digits[$i] = $carry % 58;
                $carry      = intdiv($carry, 58);
            }
            while ($carry > 0) {
                $digits[] = $carry % 58;
                $carry    = intdiv($carry, 58);
            }
        }

        $out = '';
        foreach (array_reverse($digits) as $digit) {
            $out .= self::ALPHABET[$digit];
        }
        $out = ltrim($out, '1');

        return str_repeat('1', strlen($bytes) - strlen(ltrim($bytes, "\0"))) . $out;
    }
}
