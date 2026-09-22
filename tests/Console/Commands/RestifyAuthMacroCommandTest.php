<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class RestifyAuthMacroCommandTest extends IntegrationTestCase
{
    private string $routesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routesPath = base_path('routes/api.php');

        File::ensureDirectoryExists(dirname($this->routesPath));
        File::put($this->routesPath, "<?php\n");
    }

    protected function tearDown(): void
    {
        File::delete($this->routesPath);

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
        $this->artisan('restify:auth-macro')->assertExitCode(0);

        $this->assertStringContainsString('Route::restifyAuth();', File::get($this->routesPath));
    }
}
