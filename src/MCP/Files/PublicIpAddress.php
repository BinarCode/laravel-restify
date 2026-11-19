<?php

namespace Binaryk\LaravelRestify\MCP\Files;

/**
 * Tells a publicly routable unicast address from everything a server-side fetch must not reach.
 */
final class PublicIpAddress
{
    /**
     * IPv4 blocks that are not public unicast, from the IANA special-purpose address registry.
     */
    private const NON_PUBLIC_IPV4 = [
        '0.0.0.0/8',       // "this network", includes the unspecified address
        '10.0.0.0/8',      // private
        '100.64.0.0/10',   // carrier-grade NAT
        '127.0.0.0/8',     // loopback
        '169.254.0.0/16',  // link-local, includes the cloud metadata address
        '172.16.0.0/12',   // private
        '192.0.0.0/24',    // IETF protocol assignments
        '192.0.2.0/24',    // documentation
        '192.88.99.0/24',  // 6to4 relay anycast, deprecated
        '192.168.0.0/16',  // private
        '198.18.0.0/15',   // benchmarking
        '198.51.100.0/24', // documentation
        '203.0.113.0/24',  // documentation
        '224.0.0.0/4',     // multicast
        '240.0.0.0/4',     // reserved, includes the broadcast address
    ];

    /**
     * The only IPv6 block with public unicast addresses. Loopback, unspecified, IPv4-mapped, IPv4-compatible,
     * unique local, link-local, site-local and multicast addresses all sit outside it.
     */
    private const GLOBAL_UNICAST_IPV6 = '2000::/3';

    /**
     * Blocks inside the global unicast block that are not public unicast.
     */
    private const NON_PUBLIC_IPV6 = [
        '2001::/23',     // IETF protocol assignments: Teredo, benchmarking, ORCHID, ...
        '2001:db8::/32', // documentation
        '2002::/16',     // 6to4, which embeds an IPv4 address
        '3fff::/20',     // documentation
    ];

    /**
     * The NAT64 well-known prefix. An address in it reaches the IPv4 address held in its last four bytes.
     */
    private const NAT64 = '64:ff9b::/96';

    public static function isPublic(string $address): bool
    {
        $packed = self::pack($address);

        if ($packed === null) {
            return false;
        }

        if (strlen($packed) === 4) {
            return ! self::inAnyBlock($packed, self::NON_PUBLIC_IPV4);
        }

        if (self::inBlock($packed, self::NAT64)) {
            return ! self::inAnyBlock(substr($packed, 12), self::NON_PUBLIC_IPV4);
        }

        return self::inBlock($packed, self::GLOBAL_UNICAST_IPV6)
            && ! self::inAnyBlock($packed, self::NON_PUBLIC_IPV6);
    }

    /**
     * The one spelling of the address that every parser reads the same way, or null if it is not an address.
     */
    public static function canonical(string $address): ?string
    {
        $packed = self::pack($address);

        if ($packed === null) {
            return null;
        }

        $canonical = inet_ntop($packed);

        return $canonical === false ? null : $canonical;
    }

    public static function isIpv6(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    private static function pack(string $address): ?string
    {
        // filter_var() first: it accepts dotted decimal and RFC 4291 text only, on every platform.
        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = @inet_pton($address);

        return $packed === false ? null : $packed;
    }

    /**
     * @param  list<string>  $blocks
     */
    private static function inAnyBlock(string $packed, array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (self::inBlock($packed, $block)) {
                return true;
            }
        }

        return false;
    }

    private static function inBlock(string $packed, string $block): bool
    {
        [$network, $prefix] = explode('/', $block);

        $network = (string) inet_pton($network);

        if (strlen($network) !== strlen($packed)) {
            return false;
        }

        $bytes = intdiv((int) $prefix, 8);
        $bits = (int) $prefix % 8;

        if (substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $bits)) & 0xFF;

        return (ord($packed[$bytes]) & $mask) === (ord($network[$bytes]) & $mask);
    }
}
