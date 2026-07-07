<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\McpServiceProvider;

class SkillCommandTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::partialMock()
            ->shouldReceive('remember')
            ->andReturnUsing(fn ($key, $ttl, $callback) => $callback())
            ->shouldReceive('flush')
            ->andReturn(true);

        $this->outputPath = sys_get_temp_dir().'/restify-skill-'.uniqid();
    }

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        File::deleteDirectory($this->outputPath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_generates_skill_and_openapi_files(): void
    {
        Restify::repositories([$this->repositoryClass()]);

        $this->artisan('restify:skill', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/SKILL.md');
        $this->assertFileExists($this->outputPath.'/openapi.json');

        $skill = File::get($this->outputPath.'/SKILL.md');

        $this->assertStringContainsString('filterName=value', $skill);
        $this->assertStringContainsString('Do **NOT** use the bracketed `filter[name]=value`', $skill);
        $this->assertStringContainsString('### `skill-posts`', $skill);
        $this->assertStringContainsString('**Operations:**', $skill);
        $this->assertStringContainsString('curl -X POST', $skill);

        $openApi = json_decode(File::get($this->outputPath.'/openapi.json'), true);

        $this->assertIsArray($openApi);
        $this->assertSame('3.1.0', $openApi['openapi']);
        $this->assertArrayHasKey('bearerAuth', $openApi['components']['securitySchemes']);
        $this->assertArrayHasKey('/api/restify/skill-posts', $openApi['paths']);
        $this->assertArrayHasKey('get', $openApi['paths']['/api/restify/skill-posts']);
        $this->assertArrayHasKey('post', $openApi['paths']['/api/restify/skill-posts']);
        $this->assertArrayHasKey('/api/restify/skill-posts/{repositoryId}', $openApi['paths']);
    }

    public function test_generates_skill_with_object_valued_related(): void
    {
        Restify::repositories([$this->repositoryWithObjectRelated()]);

        $this->artisan('restify:skill', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $skill = File::get($this->outputPath.'/SKILL.md');

        $this->assertStringContainsString('**Related:** `users`', $skill);
    }

    private function repositoryWithObjectRelated(): string
    {
        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'skill-related-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                ];
            }

            public static function related(): array
            {
                return [
                    'users' => BelongsToMany::make('users', UserRepository::class),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        return $repository::class;
    }

    private function repositoryClass(): string
    {
        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'skill-posts';

            public static array $match = [
                'title' => 'string',
            ];

            public static array $sort = [
                'id',
            ];

            public static array $search = [
                'title',
            ];

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                    Field::make('description'),
                ];
            }

            public function actions(RestifyRequest $request): array
            {
                return [PublishPostAction::new()];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsShow(): bool
            {
                return true;
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        return $repository::class;
    }
}
