<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class PrepareSanctumCommandTest extends IntegrationTestCase
{
    private string $tempBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempBasePath = sys_get_temp_dir().'/restify-sanctum-test-'.uniqid('', true);
        File::ensureDirectoryExists($this->tempBasePath.'/config');
        $this->app->setBasePath($this->tempBasePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    #[Test]
    public function it_fails_when_composer_lock_is_missing(): void
    {
        $this->assertFileDoesNotExist(base_path('composer.lock'));

        $this->artisan('restify:sanctum')->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_succeeds_and_uncomments_the_auth_sanctum_middleware(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), <<<'PHP'
            <?php

            return [
                'middleware' => [
                    'api',
                    // 'auth:sanctum',
                ],
            ];

            PHP);

        $this->seedValidUserModel();

        $this->artisan('restify:sanctum')->assertExitCode(Command::SUCCESS);

        $updatedContent = File::get(config_path('restify.php'));

        $this->assertStringContainsString("        'auth:sanctum',", $updatedContent);
        $this->assertStringNotContainsString("// 'auth:sanctum',", $updatedContent);
    }

    #[Test]
    public function it_fails_when_the_restify_config_is_missing(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        $this->seedValidUserModel();

        $this->assertFileDoesNotExist(config_path('restify.php'));

        $this->artisan('restify:sanctum')->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_fails_when_the_user_model_is_missing(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), <<<'PHP'
            <?php

            return [
                'middleware' => [
                    'api',
                ],
            ];

            PHP);

        $this->assertFileDoesNotExist(app_path('Models/User.php'));

        $this->artisan('restify:sanctum')->assertExitCode(Command::FAILURE);
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
