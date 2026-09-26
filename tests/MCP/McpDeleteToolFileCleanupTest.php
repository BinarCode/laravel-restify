<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class McpDeleteToolFileCleanupTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'mcp-delete-file-cleanup';

    private const DISK = 'customDisk';

    private const AVATAR_PATH = 'avatar.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DISK);

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpDeleteFileCleanupUserRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['restify.users.delete']);
        McpDeleteFileCleanupUserRepository::$prunable = true;
        Restify::$repositories = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    #[TestWith([true], 'prunable')]
    #[TestWith([false], 'not prunable')]
    public function the_delete_tool_prunes_the_stored_file_only_when_the_field_is_prunable(bool $prunable): void
    {
        McpDeleteFileCleanupUserRepository::$prunable = $prunable;
        $user = $this->userWithAvatar();

        $this->callDeleteTool($user)
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $this->assertDatabaseMissing(User::class, ['id' => $user->getKey()]);
        $prunable
            ? Storage::disk(self::DISK)->assertMissing(self::AVATAR_PATH)
            : Storage::disk(self::DISK)->assertExists(self::AVATAR_PATH);
    }

    #[Test]
    public function the_delete_tool_keeps_the_stored_file_when_the_policy_denies_delete(): void
    {
        $_SERVER['restify.users.delete'] = false;
        $user = $this->userWithAvatar();

        $this->callDeleteTool($user)
            ->assertOk()
            ->assertJsonPath('result.isError', true);

        $this->assertDatabaseHas(User::class, ['id' => $user->getKey(), 'avatar' => self::AVATAR_PATH]);
        Storage::disk(self::DISK)->assertExists(self::AVATAR_PATH);
    }

    private function userWithAvatar(): User
    {
        $avatar = UploadedFile::fake()->image('image.jpg')->storeAs('/', self::AVATAR_PATH, self::DISK);

        $user = User::factory()->create(['avatar' => $avatar]);

        Storage::disk(self::DISK)->assertExists(self::AVATAR_PATH);

        return $user;
    }

    private function callDeleteTool(User $user): TestResponse
    {
        return $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'mcp-delete-file-cleanup-users-delete-tool',
                'arguments' => ['id' => (string) $user->getKey()],
            ],
        ]);
    }
}

class McpDeleteFileCleanupUserRepository extends Repository
{
    use HasMcpTools;

    public static bool $prunable = true;

    public static $model = User::class;

    public static string $uriKey = 'mcp-delete-file-cleanup-users';

    public function fields(RestifyRequest $request): array
    {
        return [
            Image::make('avatar')
                ->disk('customDisk')
                ->storeAs('avatar.jpg')
                ->prunable(static::$prunable),
        ];
    }

    public function mcpAllowsDelete(): bool
    {
        return true;
    }
}
