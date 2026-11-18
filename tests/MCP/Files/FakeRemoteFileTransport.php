<?php

namespace Binaryk\LaravelRestify\Tests\MCP\Files;

use Binaryk\LaravelRestify\MCP\Files\PinnedRemoteFile;
use Binaryk\LaravelRestify\MCP\Files\RemoteFileTransport;

/**
 * Records what it was asked to download instead of touching the network.
 */
final class FakeRemoteFileTransport implements RemoteFileTransport
{
    /**
     * @var list<array{file: PinnedRemoteFile, maxBytes: int, timeout: int}>
     */
    public array $downloads = [];

    public function __construct(private readonly ?string $contents = 'remote file contents') {}

    public function download(PinnedRemoteFile $file, int $maxBytes, int $timeout): ?string
    {
        $this->downloads[] = ['file' => $file, 'maxBytes' => $maxBytes, 'timeout' => $timeout];

        return $this->contents;
    }
}
