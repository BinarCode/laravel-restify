<?php

namespace Binaryk\LaravelRestify\MCP\Files;

/**
 * Fetches a file from a public http or https URL for a server that must not be turned against its own network.
 *
 * The host is resolved once. Every address it resolves to has to be public, and the download then connects
 * to those addresses and to nothing else, so a host that answers differently a moment later changes nothing.
 */
final class RemoteFileFetcher
{
    public function __construct(
        private readonly HostResolver $resolver,
        private readonly RemoteFileTransport $transport,
    ) {}

    public function fetch(string $url, int $maxBytes, int $timeout): ?string
    {
        $file = $this->pin($url);

        return $file === null ? null : $this->transport->download($file, $maxBytes, $timeout);
    }

    /**
     * Resolve and validate the URL's host. Null means the URL is refused.
     */
    public function pin(string $url): ?PinnedRemoteFile
    {
        // parse_url() rewrites whitespace and control characters instead of refusing them.
        if (preg_match('/[\x00-\x20\x7f]/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $host = $this->normalizeHost($parts['host'] ?? '');
        $target = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        if ($host === null || $port < 1 || ! str_starts_with($target, '/')) {
            return null;
        }

        $addresses = $this->publicAddresses(
            PublicIpAddress::canonical($host) === null ? $this->resolver->resolve($host) : [$host]
        );

        if ($addresses === null) {
            return null;
        }

        return new PinnedRemoteFile(
            scheme: $scheme,
            host: $host,
            port: $port,
            addresses: $addresses,
            target: $target,
            authorization: isset($parts['user'])
                ? 'Basic '.base64_encode(urldecode($parts['user']).':'.urldecode($parts['pass'] ?? ''))
                : null,
        );
    }

    /**
     * The host as a lower case host name or a canonical IP address, or null when it is neither.
     */
    private function normalizeHost(string $host): ?string
    {
        $host = strtolower($host);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $address = substr($host, 1, -1);

            return PublicIpAddress::isIpv6($address) ? PublicIpAddress::canonical($address) : null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return PublicIpAddress::canonical($host);
        }

        return $this->isHostName($host) ? $host : null;
    }

    /**
     * A fully qualified ASCII host name. An internationalized name has to arrive in its xn-- form.
     */
    private function isHostName(string $host): bool
    {
        $label = '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?';

        if (strlen($host) > 253 || preg_match('/^(?:'.$label.'\.)+'.$label.'$/', $host) !== 1) {
            return false;
        }

        // A name that ends in a number is an IPv4 address in a spelling only some parsers accept, such as
        // 127.1 or 0x7f.0.0.1. No top-level domain is numeric, so nothing public is lost by refusing it.
        return preg_match('/\.(?:[0-9]+|0x[0-9a-f]*)$/', $host) !== 1;
    }

    /**
     * The addresses to connect to: the first IPv4 one, then the first IPv6 one.
     *
     * One address that must not be reached refuses the host, since which of its addresses a later
     * connection would use is not ours to choose.
     *
     * @param  list<string>  $addresses
     * @return non-empty-list<string>|null
     */
    private function publicAddresses(array $addresses): ?array
    {
        $ipv4 = null;
        $ipv6 = null;

        foreach ($addresses as $address) {
            $address = PublicIpAddress::canonical($address);

            if ($address === null || ! PublicIpAddress::isPublic($address)) {
                return null;
            }

            if (PublicIpAddress::isIpv6($address)) {
                $ipv6 ??= $address;
            } else {
                $ipv4 ??= $address;
            }
        }

        $pinned = array_values(array_filter([$ipv4, $ipv6], is_string(...)));

        return $pinned === [] ? null : $pinned;
    }
}
