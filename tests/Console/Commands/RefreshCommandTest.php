<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Fixtures\Console\RecordingCommand;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class RefreshCommandTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingCommand::$calls = [];
        RecordingCommand::$exitCodes = [];

        foreach (['route:cache', 'route:clear', 'cache:clear', 'config:cache', 'config:clear', 'view:clear'] as $name) {
            Artisan::registerCommand(new RecordingCommand($name));
        }
    }

    protected function tearDown(): void
    {
        RecordingCommand::$calls = [];
        RecordingCommand::$exitCodes = [];

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
    public function it_flushes_the_configured_repository_cache_store_when_it_differs_from_the_default(): void
    {
        config(['cache.default' => 'array']);
        config(['cache.stores.restify_repositories' => ['driver' => 'array']]);
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.store' => 'restify_repositories']);

        Cache::store('restify_repositories')->put('restify-store-key', 'value', 60);

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertFalse(Cache::store('restify_repositories')->has('restify-store-key'));
    }

    #[Test]
    public function it_does_not_touch_the_repository_cache_store_when_repository_caching_is_disabled(): void
    {
        config(['cache.default' => 'array']);
        config(['cache.stores.restify_repositories' => ['driver' => 'array']]);
        config(['restify.repositories.cache.enabled' => false]);
        config(['restify.repositories.cache.store' => 'restify_repositories']);

        Cache::store('restify_repositories')->put('restify-store-key', 'value', 60);

        $this->artisan('restify:refresh')->assertExitCode(Command::SUCCESS);

        $this->assertTrue(Cache::store('restify_repositories')->has('restify-store-key'));
    }
}
