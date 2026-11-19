<?php

namespace Binaryk\LaravelRestify\MCP\Files;

final class DnsHostResolver implements HostResolver
{
    public function resolve(string $host): array
    {
        // False when either the A or the AAAA lookup failed: half an answer is not an answer.
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            $address = match ($record['type'] ?? null) {
                'A' => $record['ip'] ?? null,
                'AAAA' => $record['ipv6'] ?? null,
                default => null,
            };

            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }
}
