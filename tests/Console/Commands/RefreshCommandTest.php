<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Fixtures\Console\RecordingCommand;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\Test;

class RefreshCommandTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingCommand::$calls = [];

        foreach (['route:cache', 'route:clear', 'cache:clear', 'config:cache', 'config:clear', 'view:clear'] as $name) {
            app(Kernel::class)->registerCommand(new RecordingCommand($name));
        }
    }

    #[Test]
    public function it_clears_every_cache_exactly_once_without_self_cancelling_steps(): void
    {
        $this->artisan('restify:refresh')->assertExitCode(0);

        $this->assertSame(
            ['route:clear', 'cache:clear', 'config:clear', 'view:clear'],
            RecordingCommand::$calls,
        );
    }
}
