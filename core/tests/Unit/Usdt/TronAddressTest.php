<?php

namespace Tests\Unit\Usdt;

use App\Services\Usdt\TronAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TronAddressTest extends TestCase
{
    public static function knownPairs(): array
    {
        return [
            'USDT contract' => ['TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', '41a614f803b6fd780986a42c78ec9c7f77e6ded13c'],
            'zero address'  => ['T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb', '410000000000000000000000000000000000000000'],
        ];
    }

    #[DataProvider('knownPairs')]
    public function test_converts_both_ways(string $base58, string $hex): void
    {
        $this->assertSame($hex, TronAddress::toHex($base58));
        $this->assertSame($base58, TronAddress::fromHex($hex));
        $this->assertSame($base58, TronAddress::fromHex(substr($hex, 2)), 'the 20-byte form a log carries');
        $this->assertSame($base58, TronAddress::fromHex(str_repeat('0', 24) . substr($hex, 2)), 'the 32-byte topic form');
    }

    public static function invalid(): array
    {
        return [
            'one character changed' => ['TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6u'],
            'one character short'   => ['TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6'],
            'not base58 (0)'        => ['T0yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb'],
            'Ethereum address'      => ['0xdAC17F958D2ee523a2206206994597C13D831ec7'],
            'empty'                 => [''],
            'spaces around'         => [' TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'],
        ];
    }

    #[DataProvider('invalid')]
    public function test_refuses_invalid_addresses(string $address): void
    {
        $this->assertFalse(TronAddress::isValid($address));
        $this->assertNull(TronAddress::toHex($address));
    }

    public function test_refuses_malformed_hex(): void
    {
        $this->assertNull(TronAddress::fromHex('42a614f803b6fd780986a42c78ec9c7f77e6ded13c'));
        $this->assertNull(TronAddress::fromHex('xyz'));
    }
}
