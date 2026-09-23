<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RestifyAuthMacroCommandTest extends IntegrationTestCase
{
    private string $tempBasePath;

    private string $routesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempBasePath = sys_get_temp_dir().'/restify-auth-macro-test-'.uniqid('', true);
        File::ensureDirectoryExists($this->tempBasePath.'/routes');
        $this->app->setBasePath($this->tempBasePath);

        $this->routesPath = base_path('routes/api.php');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    #[Test]
    public function it_is_registered_as_a_standalone_artisan_command(): void
    {
        $this->assertArrayHasKey('restify:auth-macro', Artisan::all());
    }

    #[Test]
    public function it_appends_the_restify_auth_route_when_run_standalone(): void
    {
        $this->seedRoutesFile("<?php\n");

        $this->artisan('restify:auth-macro')->assertExitCode(Command::SUCCESS);

        $this->assertStringContainsString('Route::restifyAuth();', File::get($this->routesPath));
    }

    #[Test]
    #[TestWith(["<?php\nRoute::restifyAuth();\n"], 'bare call')]
    #[TestWith(["<?php\nRoute::restifyAuth(actions: ['login']);\n"], 'customized call')]
    public function it_does_not_duplicate_an_existing_restify_auth_route(string $existingContent): void
    {
        $this->seedRoutesFile($existingContent);

        $this->artisan('restify:auth-macro')->assertExitCode(Command::SUCCESS);

        $this->assertSame(1, substr_count(File::get($this->routesPath), 'Route::restifyAuth('));
    }

    #[Test]
    public function it_is_idempotent_when_run_twice(): void
    {
        $this->seedRoutesFile("<?php\n");

        $this->artisan('restify:auth-macro')->assertExitCode(Command::SUCCESS);
        $this->artisan('restify:auth-macro')->assertExitCode(Command::SUCCESS);

        $this->assertSame(1, substr_count(File::get($this->routesPath), 'Route::restifyAuth('));
    }

    #[Test]
    public function it_fails_when_the_routes_file_is_missing(): void
    {
        File::delete($this->routesPath);

        $this->artisan('restify:auth-macro')->assertExitCode(Command::FAILURE);
    }

    private function seedRoutesFile(string $content): void
    {
        File::put($this->routesPath, $content);
    }
}
