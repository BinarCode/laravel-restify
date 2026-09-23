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
        File::ensureDirectoryExists($this->tempBasePath.'/config');
        $this->app->setBasePath($this->tempBasePath);

        // Report Sanctum as already installed so PrepareSanctumCommand does
        // not shell out to composer/artisan while this test runs.
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), "<?php\n\nreturn [\n    'middleware' => [\n        'api',\n    ],\n];\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    #[Test]
    public function it_fails_when_the_auth_macro_step_fails(): void
    {
        $this->assertFileDoesNotExist(base_path('routes/api.php'));

        $this->artisan('restify:setup-auth')->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_fails_when_the_sanctum_step_fails(): void
    {
        File::delete(base_path('composer.lock'));

        File::ensureDirectoryExists(base_path('routes'));
        File::put(base_path('routes/api.php'), "<?php\n");

        $this->artisan('restify:setup-auth')->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_succeeds_when_both_steps_succeed(): void
    {
        File::ensureDirectoryExists(base_path('routes'));
        File::put(base_path('routes/api.php'), "<?php\n");

        $this->seedValidUserModel();

        $this->artisan('restify:setup-auth')->assertExitCode(Command::SUCCESS);
    }

    private function seedValidUserModel(): void
    {
        File::ensureDirectoryExists(app_path('Models'));

        File::put(app_path('Models/User.php'), <<<'PHP'
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Illuminate\Notifications\Notifiable;

            class User extends Authenticatable
            {
                use HasFactory, Notifiable;
            }

            PHP);
    }
}
