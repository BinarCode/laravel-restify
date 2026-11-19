<?php

namespace Binaryk\LaravelRestify\MCP\Files;

/**
 * A remote file whose host has been resolved and validated: where to connect, and what to ask for.
 *
 * Whoever downloads it connects to one of the addresses and never resolves the host again. The host is only
 * sent in the Host header and checked against the TLS certificate.
 */
final readonly class PinnedRemoteFile
{
    /**
     * @param  'http'|'https'  $scheme
     * @param  string  $host  The host the URL names: a lower case host name, or an IP address without brackets.
     * @param  non-empty-list<string>  $addresses  The validated addresses to connect to, in order of preference.
     * @param  string  $target  The path and query to request, starting with a slash.
     * @param  string|null  $authorization  The Authorization header for credentials the URL carried.
     */
    public function __construct(
        public string $scheme,
        public string $host,
        public int $port,
        public array $addresses,
        public string $target,
        public ?string $authorization = null,
    ) {}

    /**
     * The URL to open for one of the addresses. Its authority is the address, so nothing is left to resolve.
     */
    public function urlFor(string $address): string
    {
        return sprintf('%s://%s:%d%s', $this->scheme, self::bracket($address), $this->port, $this->target);
    }

    public function hostHeader(): string
    {
        $host = self::bracket($this->host);

        return $this->port === ($this->scheme === 'https' ? 443 : 80)
            ? $host
            : $host.':'.$this->port;
    }

    public function hostIsAddress(): bool
    {
        return PublicIpAddress::canonical($this->host) !== null;
    }

    private static function bracket(string $host): string
    {
        return PublicIpAddress::isIpv6($host) ? '['.$host.']' : $host;
    }
}
