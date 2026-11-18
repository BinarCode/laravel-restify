<?php

namespace Binaryk\LaravelRestify\MCP\Files;

interface HostResolver
{
    /**
     * Resolve a host name to every IPv4 and IPv6 address it has.
     *
     * An empty list means the name could not be resolved, whatever the reason.
     *
     * @return list<string>
     */
    public function resolve(string $host): array;
}
