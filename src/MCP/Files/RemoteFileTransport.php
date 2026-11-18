<?php

namespace Binaryk\LaravelRestify\MCP\Files;

interface RemoteFileTransport
{
    /**
     * Download the file from one of its pinned addresses, without resolving its host and without
     * following redirects.
     *
     * @return string|null The body of a 2xx response of at most $maxBytes bytes, or null.
     */
    public function download(PinnedRemoteFile $file, int $maxBytes, int $timeout): ?string;
}
