<?php

namespace Binaryk\LaravelRestify\Tests\MCP\Files;

use Binaryk\LaravelRestify\MCP\Files\PinnedRemoteFile;
use Binaryk\LaravelRestify\MCP\Files\StreamRemoteFileTransport;
use PHPUnit\Framework\TestCase;

/**
 * Downloads from a PHP built-in server on the loopback address, so no test here leaves the machine.
 *
 * The files are pinned by hand: the transport has no opinion on which addresses may be reached, that is
 * RemoteFileFetcher's job. Their host is a name under .test, which never resolves, so a download can only
 * succeed by connecting to the pinned address.
 */
class StreamRemoteFileTransportTest extends TestCase
{
    private const HOST = 'files.example.test';

    /**
     * @var resource|null
     */
    private static $server = null;

    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();

        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:'.self::$port, __DIR__.'/fixtures/remote-file-server.php'],
            [['file', $null, 'r'], ['file', $null, 'w'], ['file', $null, 'w']],
            $pipes,
        );

        if ($server === false) {
            return;
        }

        self::$server = $server;

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', self::$port, $errorCode, $errorMessage, 0.2);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(100_000);
        }

        self::stopServer();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopServer();
    }

    public function test_it_connects_to_the_pinned_address_and_names_the_host_in_the_host_header(): void
    {
        $response = $this->download($this->file('/file?size=large'));

        $this->assertSame([
            'method' => 'GET',
            'uri' => '/file?size=large',
            'host' => self::HOST.':'.self::$port,
            'authorization' => null,
        ], json_decode((string) $response, true));
    }

    public function test_it_sends_the_credentials_the_url_carried(): void
    {
        $response = $this->download($this->file('/file', authorization: 'Basic '.base64_encode('reader:secret')));

        $this->assertSame(
            'Basic '.base64_encode('reader:secret'),
            json_decode((string) $response, true)['authorization']
        );
    }

    public function test_it_tries_the_next_pinned_address_when_one_does_not_answer(): void
    {
        // The server listens on the IPv4 loopback address only.
        $response = $this->download($this->file('/file', addresses: ['::1', '127.0.0.1']));

        $this->assertSame('/file', json_decode((string) $response, true)['uri']);
    }

    public function test_it_returns_null_when_no_pinned_address_answers(): void
    {
        $this->requireServer();

        $file = new PinnedRemoteFile('http', self::HOST, self::freePort(), ['127.0.0.1'], '/file');

        $this->assertNull((new StreamRemoteFileTransport)->download($file, 1024, 2));
    }

    public function test_it_returns_a_body_up_to_the_size_limit_and_nothing_beyond_it(): void
    {
        $this->assertSame(str_repeat('a', 2048), $this->download($this->file('/bytes?count=2048'), maxBytes: 2048));
        $this->assertNull($this->download($this->file('/bytes?count=2049'), maxBytes: 2048));
    }

    public function test_it_does_not_follow_a_redirect_or_return_its_body(): void
    {
        $this->assertNull($this->download($this->file('/redirect')));
    }

    public function test_it_does_not_return_the_body_of_an_error_response(): void
    {
        $this->assertNull($this->download($this->file('/missing')));
    }

    public function test_it_gives_up_after_the_timeout(): void
    {
        $startedAt = microtime(true);

        $this->assertNull($this->download($this->file('/slow'), timeout: 1));
        $this->assertLessThan(1.9, microtime(true) - $startedAt);
    }

    public function test_it_asks_for_the_certificate_of_the_host_and_follows_no_redirect(): void
    {
        $file = new PinnedRemoteFile('https', self::HOST, 443, ['93.184.216.34'], '/photo.png');

        $options = (new StreamRemoteFileTransport)->contextOptions($file, 7);

        $this->assertSame('https://93.184.216.34:443/photo.png', $file->urlFor('93.184.216.34'));
        $this->assertSame(['Host: '.self::HOST], $options['http']['header']);
        $this->assertSame(0, $options['http']['follow_location']);
        $this->assertSame(0, $options['http']['max_redirects']);
        $this->assertSame(7, $options['http']['timeout']);
        $this->assertArrayNotHasKey('proxy', $options['http']);

        $this->assertSame(self::HOST, $options['ssl']['peer_name']);
        $this->assertTrue($options['ssl']['SNI_enabled']);
        $this->assertTrue($options['ssl']['verify_peer']);
        $this->assertTrue($options['ssl']['verify_peer_name']);
        $this->assertFalse($options['ssl']['allow_self_signed']);
    }

    public function test_it_adds_the_port_and_the_credentials_to_the_request_headers(): void
    {
        $file = new PinnedRemoteFile('https', self::HOST, 8443, ['93.184.216.34'], '/photo.png', 'Basic cmVhZGVyOnNlY3JldA==');

        $this->assertSame(
            ['Host: '.self::HOST.':8443', 'Authorization: Basic cmVhZGVyOnNlY3JldA=='],
            (new StreamRemoteFileTransport)->contextOptions($file, 7)['http']['header']
        );
    }

    public function test_it_leaves_the_certificate_name_to_the_url_when_the_host_is_an_address(): void
    {
        $file = new PinnedRemoteFile('https', '93.184.216.34', 443, ['93.184.216.34'], '/photo.png');

        $options = (new StreamRemoteFileTransport)->contextOptions($file, 7);

        $this->assertArrayNotHasKey('peer_name', $options['ssl']);
        $this->assertTrue($options['ssl']['verify_peer']);
        $this->assertTrue($options['ssl']['verify_peer_name']);
    }

    /**
     * @param  non-empty-list<string>  $addresses
     */
    private function file(string $target, array $addresses = ['127.0.0.1'], ?string $authorization = null): PinnedRemoteFile
    {
        return new PinnedRemoteFile('http', self::HOST, self::$port, $addresses, $target, $authorization);
    }

    private function download(PinnedRemoteFile $file, int $maxBytes = 4096, int $timeout = 5): ?string
    {
        $this->requireServer();

        return (new StreamRemoteFileTransport)->download($file, $maxBytes, $timeout);
    }

    private function requireServer(): void
    {
        if (self::$server === null) {
            $this->markTestSkipped('The PHP built-in server could not be started.');
        }
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    private static function stopServer(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }
}
