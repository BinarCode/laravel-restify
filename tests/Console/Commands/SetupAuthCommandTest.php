<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class SetupAuthCommandTest extends IntegrationTestCase
{
    private string $tempBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempBasePath = sys_get_temp_dir().'/restify-setup-auth-test-'.uniqid('', true);
        File::ensureDirectoryExists($this->tempBasePath);
        $this->app->setBasePath($this->tempBasePath);

        // Report Sanctum as already installed so PrepareSanctumCommand does
        // not shell out to composer/artisan while this test runs.
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        // No routes/api.php in this temp base path, so the auth-macro step fails.
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    #[Test]
    public function it_fails_when_the_auth_macro_step_fails(): void
    {
        $this->artisan('restify:setup-auth')->assertExitCode(Command::FAILURE);
    }
}
