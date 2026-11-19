<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Files\DnsHostResolver;
use Binaryk\LaravelRestify\MCP\Files\HostResolver;
use Binaryk\LaravelRestify\MCP\Files\RemoteFileTransport;
use Binaryk\LaravelRestify\MCP\Files\StreamRemoteFileTransport;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Binaryk\LaravelRestify\Tests\MCP\Files\FakeHostResolver;
use Binaryk\LaravelRestify\Tests\MCP\Files\FakeRemoteFileTransport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The store tool turns a URL an agent supplies for a file field into an upload. The resolver and the
 * transport are replaced here, so nothing is looked up or downloaded for real.
 */
class McpRemoteFileIngestionTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'mcp-remote-file-ingestion';

    private const URL = 'https://files.example.com/images/photo.png';

    private const PUBLIC_ADDRESS = '93.184.216.34';

    /**
     * @var list<string>
     */
    private array $temporaryUploadsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(McpRemoteFileIngestionUserRepository::DISK);

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpRemoteFileIngestionUserRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $this->temporaryUploadsBefore = $this->temporaryUploads();
    }

    protected function tearDown(): void
    {
        // The store tool leaves the file it downloaded in the temporary directory.
        foreach (array_diff($this->temporaryUploads(), $this->temporaryUploadsBefore) as $upload) {
            @unlink($upload);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_the_package_resolves_over_dns_and_downloads_over_php_streams_by_default(): void
    {
        $this->assertInstanceOf(DnsHostResolver::class, $this->app->make(HostResolver::class));
        $this->assertInstanceOf(StreamRemoteFileTransport::class, $this->app->make(RemoteFileTransport::class));
    }

    public function test_a_public_url_becomes_an_upload_downloaded_from_the_validated_address(): void
    {
        $this->fake(new FakeHostResolver([self::PUBLIC_ADDRESS]), $transport = new FakeRemoteFileTransport('png bytes'));

        config(['restify.mcp.files.max_bytes' => 2048, 'restify.mcp.files.timeout' => 7]);

        $upload = (new McpRemoteFileIngestionUserRepository)->resolveUploadedFileFromInput(self::URL);

        $this->assertInstanceOf(UploadedFile::class, $upload);
        $this->assertSame('photo.png', $upload->getClientOriginalName());
        $this->assertSame('png bytes', $upload->getContent());

        $this->assertCount(1, $transport->downloads);
        $this->assertSame([self::PUBLIC_ADDRESS], $transport->downloads[0]['file']->addresses);
        $this->assertSame('files.example.com', $transport->downloads[0]['file']->host);
        $this->assertSame(2048, $transport->downloads[0]['maxBytes']);
        $this->assertSame(7, $transport->downloads[0]['timeout']);
    }

    public function test_a_host_that_turns_private_after_it_was_checked_is_not_downloaded_from(): void
    {
        // Public for the first lookup, loopback for every later one.
        $this->fake(
            $resolver = new FakeHostResolver([self::PUBLIC_ADDRESS], ['127.0.0.1']),
            $transport = new FakeRemoteFileTransport,
        );

        $repository = new McpRemoteFileIngestionUserRepository;

        $this->assertTrue($repository::isSafePublicUrl(self::URL));
        $this->assertNull($repository->resolveUploadedFileFromInput(self::URL));
        $this->assertSame([], $transport->downloads);
        $this->assertNotSame([], $resolver->lookups);
    }

    public function test_the_download_connects_to_the_addresses_validated_by_its_own_lookup(): void
    {
        // isSafePublicUrl() sees the first answer. The download resolves for itself and pins what it validated.
        $this->fake(
            new FakeHostResolver([self::PUBLIC_ADDRESS], ['1.1.1.1']),
            $transport = new FakeRemoteFileTransport,
        );

        $this->assertNotNull((new McpRemoteFileIngestionUserRepository)->resolveUploadedFileFromInput(self::URL));

        $this->assertCount(1, $transport->downloads);
        $this->assertSame(['1.1.1.1'], $transport->downloads[0]['file']->addresses);
    }

    public function test_a_host_that_resolves_to_a_private_address_is_never_downloaded_from(): void
    {
        $this->fake(new FakeHostResolver(['10.0.0.5']), $transport = new FakeRemoteFileTransport);

        $repository = new McpRemoteFileIngestionUserRepository;

        $this->assertFalse($repository::isSafePublicUrl(self::URL));
        $this->assertNull($repository->resolveUploadedFileFromInput(self::URL));
        $this->assertSame([], $transport->downloads);
    }

    public function test_a_download_that_fails_is_not_an_upload(): void
    {
        $this->fake(new FakeHostResolver([self::PUBLIC_ADDRESS]), new FakeRemoteFileTransport(null));

        $this->assertNull((new McpRemoteFileIngestionUserRepository)->resolveUploadedFileFromInput(self::URL));
    }

    /**
     * Addresses the previous check let through, next to ones it already refused.
     *
     * @return array<string, array{string}>
     */
    public static function urlsThatAreNotSafe(): array
    {
        return [
            'carrier-grade NAT' => ['http://100.64.0.1/'],
            'IETF protocol assignments' => ['http://192.0.0.8/'],
            'documentation' => ['http://192.0.2.1/'],
            'benchmarking' => ['http://198.18.0.1/'],
            'multicast' => ['http://224.0.0.1/'],
            'IPv6 multicast' => ['http://[ff02::1]/'],
            'IPv6 site-local' => ['http://[fec0::1]/'],
            'IPv6 documentation' => ['http://[2001:db8::1]/'],
            '6to4 of a loopback address' => ['http://[2002:7f00:1::1]/'],
            'NAT64 of a loopback address' => ['http://[64:ff9b::7f00:1]/'],
            'Teredo' => ['http://[2001:0:7f00:1::1]/'],
            'loopback' => ['http://127.0.0.1/secret'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data'],
            'private' => ['http://192.168.0.1/'],
            'IPv6 loopback' => ['http://[::1]/'],
            'IPv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'unique local' => ['http://[fd00::1]/'],
            'ftp' => ['ftp://example.com/file.txt'],
            'file' => ['file:///etc/passwd'],
        ];
    }

    #[DataProvider('urlsThatAreNotSafe')]
    public function test_a_url_that_names_an_address_that_must_not_be_reached_is_not_safe(string $url): void
    {
        $this->fake($resolver = new FakeHostResolver([self::PUBLIC_ADDRESS]), $transport = new FakeRemoteFileTransport);

        $repository = new McpRemoteFileIngestionUserRepository;

        $this->assertFalse($repository::isSafePublicUrl($url));
        $this->assertNull($repository->resolveUploadedFileFromInput($url));
        $this->assertSame([], $resolver->lookups);
        $this->assertSame([], $transport->downloads);
    }

    public function test_the_store_tool_stores_the_file_it_downloaded_from_the_validated_address(): void
    {
        $this->fake(new FakeHostResolver([self::PUBLIC_ADDRESS]), $transport = new FakeRemoteFileTransport('png bytes'));

        $this->callStoreTool(['avatar' => self::URL])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $user = User::query()->where('email', 'jane@example.com')->sole();

        $this->assertNotNull($user->avatar);
        $this->assertSame('png bytes', Storage::disk(McpRemoteFileIngestionUserRepository::DISK)->get($user->avatar));
        $this->assertCount(1, $transport->downloads);
        $this->assertSame([self::PUBLIC_ADDRESS], $transport->downloads[0]['file']->addresses);
    }

    public function test_the_store_tool_downloads_nothing_from_a_host_that_resolves_to_a_private_address(): void
    {
        $this->fake(new FakeHostResolver(['192.168.1.10']), $transport = new FakeRemoteFileTransport('private bytes'));

        $this->callStoreTool(['avatar' => self::URL])->assertOk();

        $this->assertSame([], $transport->downloads);
        $this->assertSame([], Storage::disk(McpRemoteFileIngestionUserRepository::DISK)->allFiles());
    }

    /**
     * @return list<string>
     */
    private function temporaryUploads(): array
    {
        return glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'restify_mcp_upload_*') ?: [];
    }

    private function fake(FakeHostResolver $resolver, FakeRemoteFileTransport $transport): void
    {
        $this->app->instance(HostResolver::class, $resolver);
        $this->app->instance(RemoteFileTransport::class, $transport);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callStoreTool(array $arguments): TestResponse
    {
        return $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => McpRemoteFileIngestionUserRepository::uriKey().'-store-tool',
                'arguments' => $arguments + [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'password' => 'secret',
                ],
            ],
        ]);
    }
}

class McpRemoteFileIngestionUserRepository extends Repository
{
    use HasMcpTools;

    public const DISK = 'customDisk';

    public static $model = User::class;

    public static string $uriKey = 'mcp-remote-file-ingestion-users';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
            field('password'),
            File::make('avatar')->disk(self::DISK),
        ];
    }

    public function mcpAllowsStore(): bool
    {
        return true;
    }
}
