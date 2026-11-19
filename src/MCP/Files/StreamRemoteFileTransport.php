<?php

namespace Binaryk\LaravelRestify\MCP\Files;

/**
 * Downloads over PHP's http stream wrapper. The URL it opens names an address, never a host, so the
 * wrapper has nothing to resolve.
 */
final class StreamRemoteFileTransport implements RemoteFileTransport
{
    public function download(PinnedRemoteFile $file, int $maxBytes, int $timeout): ?string
    {
        $context = stream_context_create($this->contextOptions($file, $timeout));

        foreach ($file->addresses as $address) {
            $stream = $this->open($file->urlFor($address), $context);

            // No HTTP response from this address. The next one was validated too, so try it.
            if ($stream === null) {
                continue;
            }

            try {
                return $this->read($stream, $maxBytes);
            } finally {
                fclose($stream);
            }
        }

        return null;
    }

    /**
     * @param  resource  $context
     * @return resource|null
     */
    private function open(string $url, $context)
    {
        // fopen() reports an address that does not answer as a warning. Here that is an expected outcome.
        set_error_handler(static fn (): bool => true);

        try {
            $stream = fopen($url, 'rb', false, $context);
        } finally {
            restore_error_handler();
        }

        return $stream === false ? null : $stream;
    }

    /**
     * @return array{http: array<string, mixed>, ssl: array<string, mixed>}
     */
    public function contextOptions(PinnedRemoteFile $file, int $timeout): array
    {
        $headers = ['Host: '.$file->hostHeader()];

        if ($file->authorization !== null) {
            $headers[] = 'Authorization: '.$file->authorization;
        }

        $ssl = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ];

        if (! $file->hostIsAddress()) {
            // The URL names an address, so say which name the certificate must be valid for.
            $ssl['peer_name'] = $file->host;
            $ssl['SNI_enabled'] = true;
        }

        return [
            'http' => [
                'method' => 'GET',
                'header' => $headers,
                'timeout' => $timeout,
                'follow_location' => 0,
                'max_redirects' => 0,
                // Every status opens the stream, so a failure to open means the address did not answer.
                'ignore_errors' => true,
            ],
            'ssl' => $ssl,
        ];
    }

    /**
     * @param  resource  $stream
     */
    private function read($stream, int $maxBytes): ?string
    {
        if (! $this->succeeded(stream_get_meta_data($stream)['wrapper_data'] ?? null)) {
            return null;
        }

        $contents = stream_get_contents($stream, $maxBytes + 1);

        if ($contents === false || strlen($contents) > $maxBytes) {
            return null;
        }

        // A read that timed out leaves a truncated body behind.
        return stream_get_meta_data($stream)['timed_out'] ? null : $contents;
    }

    /**
     * Only a 2xx response is a file. A redirect is not followed, and its body is not one.
     */
    private function succeeded(mixed $responseHeaders): bool
    {
        if (! is_array($responseHeaders)) {
            return false;
        }

        $status = null;

        foreach ($responseHeaders as $header) {
            if (is_string($header) && preg_match('#^HTTP/\d+(?:\.\d+)?\s+(\d{3})\b#', $header, $matches) === 1) {
                $status = (int) $matches[1];
            }
        }

        return $status !== null && $status >= 200 && $status < 300;
    }
}
