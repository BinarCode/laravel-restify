<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Console\RecordingCommand;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionProperty;

class RefreshCommandTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private ?string $originalPostRepositoryCacheStore;

    /** @var array<int, string> */
    private array $originalPostRepositoryCacheTags;

    /** @var array<class-string<Repository>, true> */
    private array $originalBootedRepositories;

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
        $this->originalBootedRepositories = $this->bootedRepositoriesProperty()->getValue();
    }

    protected function tearDown(): void
    {
        RecordingCommand::$calls = [];
        RecordingCommand::$exitCodes = [];
        PostRepository::$cacheStore = $this->originalPostRepositoryCacheStore;
        PostRepository::$cacheTags = $this->originalPostRepositoryCacheTags;
        $this->bootedRepositoriesProperty()->setValue(null, $this->originalBootedRepositories);

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
    public function it_busts_the_cache_a_fresh_artisan_process_would_see_without_touching_unrelated_keys(): void
    {
        config(['cache.default' => 'array']);
        $this->enableRepositoryCache();

        Post::factory()->create();

        $this->getJson(PostRepository::route())->assertOk();

        Cache::put('unrelated-key', 'unrelated-value', 60);

        $this->recordQueries();
        $this->getJson(PostRepository::route())->assertOk();
        $onHit = count($this->selectsAgainst(Post::class));

        $this->simulateFreshArtisanProcess();

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->recordQueries();
        $this->getJson(PostRepository::route())->assertOk();
        $onMiss = count($this->selectsAgainst(Post::class));

        $this->assertGreaterThan(
            $onHit,
            $onMiss,
            'restify:refresh should have busted the cache. executed: '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );
        $this->assertTrue(Cache::has('unrelated-key'));
    }

    #[Test]
    public function it_leaves_cache_entries_in_place_when_repository_caching_is_disabled(): void
    {
        config(['cache.default' => 'array']);
        $this->disableRepositoryCache();

        Cache::put('restify:repository:posts:index:test', 'cached-value', 60);

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertTrue(Cache::has('restify:repository:posts:index:test'));
    }

    #[TestWith(['file'])]
    #[TestWith(['database'])]
    #[Test]
    public function it_flushes_the_whole_store_when_it_does_not_support_tagging(string $store): void
    {
        if ($store === 'database') {
            $this->createCacheTable();
        }

        config(['cache.default' => $store]);
        $this->enableRepositoryCache();

        Post::factory()->create();

        $this->getJson(PostRepository::route())->assertOk();
        Cache::put('unrelated-key', 'unrelated-value', 60);
        $this->assertTrue(Cache::has('unrelated-key'));

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertFalse(
            Cache::has('unrelated-key'),
            "a non-taggable [{$store}] store should be flushed entirely, as documented",
        );
    }

    private function bootedRepositoriesProperty(): ReflectionProperty
    {
        $property = new ReflectionProperty(Repository::class, 'booted');
        $property->setAccessible(true);

        return $property;
    }

    /**
     * A real artisan process never boots a repository before calling
     * `restify:refresh` - it goes straight to `clearCache()` on the class.
     */
    private function simulateFreshArtisanProcess(): void
    {
        $booted = $this->bootedRepositoriesProperty()->getValue();
        unset($booted[PostRepository::class]);
        $this->bootedRepositoriesProperty()->setValue(null, $booted);

        PostRepository::$cacheTags = [];
    }

    private function createCacheTable(): void
    {
        if (Schema::hasTable('cache')) {
            return;
        }

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->unique();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }
}
