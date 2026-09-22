<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class SetupAuthCommandTest extends IntegrationTestCase
{
    private string $composerLockPath;

    private ?string $originalComposerLock;

    private string $routesPath;

    private ?string $originalRoutesContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerLockPath = base_path('composer.lock');
        $this->originalComposerLock = File::exists($this->composerLockPath)
            ? File::get($this->composerLockPath)
            : null;

        // Report Sanctum as already installed so PrepareSanctumCommand does
        // not shell out to composer/artisan while this test runs.
        File::put($this->composerLockPath, json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        $this->routesPath = base_path('routes/api.php');
        $this->originalRoutesContent = File::exists($this->routesPath)
            ? File::get($this->routesPath)
            : null;
        File::delete($this->routesPath);
    }

    protected function tearDown(): void
    {
        $this->originalComposerLock === null
            ? File::delete($this->composerLockPath)
            : File::put($this->composerLockPath, $this->originalComposerLock);

        $this->originalRoutesContent === null
            ? File::delete($this->routesPath)
            : File::put($this->routesPath, $this->originalRoutesContent);

        parent::tearDown();
    }

    #[Test]
    public function it_fails_when_the_auth_macro_step_fails(): void
    {
        $this->artisan('restify:setup-auth')->assertExitCode(Command::FAILURE);
    }
}
