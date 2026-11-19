<?php

namespace Binaryk\LaravelRestify\Tests\MCP\Files;

use Binaryk\LaravelRestify\MCP\Files\PublicIpAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PublicIpAddressTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function publicAddresses(): array
    {
        return self::named([
            // IPv4, including the last address before and the first after every refused block.
            '1.1.1.1',
            '8.8.8.8',
            '93.184.216.34',
            '9.255.255.255',
            '11.0.0.0',
            '100.63.255.255',
            '100.128.0.0',
            '126.255.255.255',
            '128.0.0.0',
            '169.253.255.255',
            '169.255.0.0',
            '172.15.255.255',
            '172.32.0.0',
            '191.255.255.255',
            '192.0.1.0',
            '192.0.3.0',
            '192.88.98.255',
            '192.88.100.0',
            '192.167.255.255',
            '192.169.0.0',
            '198.17.255.255',
            '198.20.0.0',
            '198.51.99.255',
            '198.51.101.0',
            '203.0.112.255',
            '203.0.114.0',
            '223.255.255.255',

            // IPv6 global unicast.
            '2606:4700:4700::1111',
            '2001:4860:4860::8888',
            '2a00:1450:4001:81b::200e',
            '2001:200::1',
            '2001:db7:ffff:ffff:ffff:ffff:ffff:ffff',
            '2001:db9::',
            '2003::1',
            '2400:cb00::1',
            '3ffe:ffff::1',
            '3fff:1000::',

            // NAT64 of a public IPv4 address.
            '64:ff9b::808:808',
            '64:ff9b::5db8:d822',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonPublicAddresses(): array
    {
        return self::named([
            // Unspecified and "this network".
            '0.0.0.0',
            '0.1.2.3',
            '0.255.255.255',
            // Private.
            '10.0.0.0',
            '10.255.255.255',
            '172.16.0.0',
            '172.31.255.255',
            '192.168.0.0',
            '192.168.255.255',
            // Carrier-grade NAT.
            '100.64.0.0',
            '100.127.255.255',
            // Loopback.
            '127.0.0.1',
            '127.255.255.255',
            // Link-local, with the cloud metadata address.
            '169.254.0.0',
            '169.254.169.254',
            '169.254.255.255',
            // IETF protocol assignments, documentation, 6to4 relays, benchmarking.
            '192.0.0.0',
            '192.0.0.8',
            '192.0.0.255',
            '192.0.2.1',
            '192.88.99.1',
            '198.18.0.0',
            '198.19.255.255',
            '198.51.100.1',
            '203.0.113.255',
            // Multicast, reserved, broadcast.
            '224.0.0.0',
            '239.255.255.255',
            '240.0.0.0',
            '255.255.255.255',

            // IPv6 unspecified and loopback.
            '::',
            '::1',
            // IPv4-mapped, whatever the IPv4 address is, and its relatives.
            '::ffff:127.0.0.1',
            '::ffff:10.0.0.1',
            '::ffff:169.254.169.254',
            '::ffff:8.8.8.8',
            '::ffff:7f00:1',
            '::127.0.0.1',
            '::8.8.8.8',
            '::ffff:0:127.0.0.1',
            // NAT64 of an IPv4 address that is not public, and the local-use NAT64 prefix.
            '64:ff9b::7f00:1',
            '64:ff9b::a00:1',
            '64:ff9b::a9fe:a9fe',
            '64:ff9b::6440:1',
            '64:ff9b:1::808:808',
            // Discard-only, unallocated.
            '100::1',
            '1::1',
            '4000::1',
            '5f00::1',
            'e000::1',
            // IETF protocol assignments: Teredo, benchmarking, ORCHID.
            '2001::1',
            '2001:0:7f00:1::1',
            '2001:2::1',
            '2001:10::1',
            '2001:20::1',
            '2001:1ff:ffff:ffff:ffff:ffff:ffff:ffff',
            // Documentation.
            '2001:db8::1',
            '2001:db8:ffff:ffff:ffff:ffff:ffff:ffff',
            '3fff::1',
            '3fff:fff:ffff:ffff:ffff:ffff:ffff:ffff',
            // 6to4, whatever the embedded IPv4 address is.
            '2002::1',
            '2002:7f00:1::1',
            '2002:a00:1::1',
            '2002:808:808::1',
            // Unique local, link-local, site-local, multicast.
            'fc00::1',
            'fd12:3456:789a::1',
            'fe80::1',
            'febf:ffff::1',
            'fec0::1',
            'ff02::1',
            'ff0e::1',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function stringsThatAreNotAddresses(): array
    {
        return self::named([
            '',
            'example.com',
            'localhost',
            // Spellings of 127.0.0.1 and 10.0.0.1 that some parsers accept.
            '127.1',
            '2130706433',
            '0x7f000001',
            '0x7f.0.0.1',
            '017700000001',
            '0177.0.0.1',
            '010.0.0.1',
            '127.0.0.1.',
            '1.2.3',
            '1.2.3.4.5',
            '256.1.1.1',
            ' 1.1.1.1',
            '1.1.1.1 ',
            "1.1.1.1\n",
            '1.1.1.1/32',
            '[::1]',
            'fe80::1%eth0',
            '::ffff:999.1.1.1',
            '2606:4700:4700::1111::',
        ]);
    }

    #[DataProvider('publicAddresses')]
    public function test_a_public_unicast_address_is_public(string $address): void
    {
        $this->assertTrue(PublicIpAddress::isPublic($address));
    }

    #[DataProvider('nonPublicAddresses')]
    public function test_an_address_that_must_not_be_reached_is_not_public(string $address): void
    {
        $this->assertFalse(PublicIpAddress::isPublic($address));
    }

    #[DataProvider('stringsThatAreNotAddresses')]
    public function test_a_string_that_is_not_an_address_is_not_public(string $address): void
    {
        $this->assertFalse(PublicIpAddress::isPublic($address));
        $this->assertNull(PublicIpAddress::canonical($address));
    }

    public function test_an_address_has_one_canonical_spelling(): void
    {
        $this->assertSame('93.184.216.34', PublicIpAddress::canonical('93.184.216.34'));
        $this->assertSame('2001:db8::1', PublicIpAddress::canonical('2001:0DB8:0000:0000:0000:0000:0000:0001'));
        $this->assertSame('::1', PublicIpAddress::canonical('0:0:0:0:0:0:0:1'));
        $this->assertSame('2606:4700:4700::1111', PublicIpAddress::canonical('2606:4700:4700:0::1111'));
    }

    public function test_it_tells_the_address_families_apart(): void
    {
        $this->assertTrue(PublicIpAddress::isIpv6('2606:4700:4700::1111'));
        $this->assertTrue(PublicIpAddress::isIpv6('::ffff:8.8.8.8'));
        $this->assertFalse(PublicIpAddress::isIpv6('8.8.8.8'));
        $this->assertFalse(PublicIpAddress::isIpv6('example.com'));
    }

    /**
     * @param  list<string>  $values
     * @return array<string, array{string}>
     */
    private static function named(array $values): array
    {
        $cases = [];

        foreach ($values as $value) {
            $cases[json_encode($value, JSON_THROW_ON_ERROR)] = [$value];
        }

        return $cases;
    }
}
