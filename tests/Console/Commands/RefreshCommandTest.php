<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Fixtures\Console\RecordingCommand;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class RefreshCommandTest extends IntegrationTestCase
{
    private ?string $originalPostRepositoryCacheStore;

    private array $originalPostRepositoryCacheTags;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingCommand::$calls = [];
        RecordingCommand::$exitCodes = [];

        foreach (['route:cache', 'route:clear', 'cache:clear', 'config:cache', 'config:clear', 'view:clear'] as $name) {
            Artisan::registerCommand(new RecordingCommand($name));
        }

        $this->originalPostRepositoryCacheStore = PostRepository::$cacheStore;
        $this->originalPostRepositoryCacheTags = PostRepository::$cacheTags;
    }

    protected function tearDown(): void
    {
        RecordingCommand::$calls = [];
        RecordingCommand::$exitCodes = [];
        PostRepository::$cacheStore = $this->originalPostRepositoryCacheStore;
        PostRepository::$cacheTags = $this->originalPostRepositoryCacheTags;

        parent::tearDown();
    }

    #[Test]
    public function it_clears_every_cache_exactly_once_without_self_cancelling_steps(): void
    {
        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertSame(
            ['route:clear', 'cache:clear', 'config:clear', 'view:clear'],
            RecordingCommand::$calls,
        );
    }

    #[Test]
    public function it_fails_when_a_sub_command_fails(): void
    {
        RecordingCommand::$exitCodes['cache:clear'] = Command::FAILURE;

        $this->artisan('restify:refresh')->assertExitCode(Command::FAILURE);

        $this->assertSame(
            ['route:clear', 'cache:clear', 'config:clear', 'view:clear'],
            RecordingCommand::$calls,
        );
    }

    #[Test]
    public function it_clears_a_repositorys_cache_on_its_own_store_without_touching_unrelated_keys(): void
    {
        config(['cache.stores.restify_repositories' => ['driver' => 'array']]);
        $this->enableRepositoryCache();
        PostRepository::$cacheStore = 'restify_repositories';

        // The default tags a repository's clearCache() resolves, whether or
        // not it has ever been instantiated (see InteractsWithCache::resolvedCacheTags()).
        $tags = ['restify', 'repositories', PostRepository::uriKey()];

        Cache::store('restify_repositories')->tags($tags)->put('restify:repository:posts:index:test', 'cached-value', 60);
        Cache::store('restify_repositories')->put('unrelated-key', 'unrelated-value', 60);

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertFalse(
            Cache::store('restify_repositories')->tags($tags)->has('restify:repository:posts:index:test'),
        );
        $this->assertTrue(Cache::store('restify_repositories')->has('unrelated-key'));
    }
}
