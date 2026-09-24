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

    private string|false $originalPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempBasePath = sys_get_temp_dir().'/restify-sanctum-test-'.uniqid('', true);
        File::ensureDirectoryExists($this->tempBasePath.'/config');
        $this->app->setBasePath($this->tempBasePath);

        $this->originalPath = getenv('PATH');
    }

    protected function tearDown(): void
    {
        putenv($this->originalPath === false ? 'PATH' : "PATH={$this->originalPath}");

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

        $this->assertStringContainsString('HasApiTokens', File::get(app_path('Models/User.php')));
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

    #[Test]
    public function it_succeeds_without_rewriting_when_the_user_model_already_uses_has_api_tokens(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), "<?php\n\nreturn [\n    'middleware' => [\n        'api',\n        'auth:sanctum',\n    ],\n];\n");

        $userModelContent = <<<'PHP'
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Illuminate\Notifications\Notifiable;
            use Laravel\Sanctum\HasApiTokens;

            class User extends Authenticatable
            {
                use HasApiTokens, HasFactory, Notifiable;
            }

            PHP;

        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/User.php'), $userModelContent);

        $this->artisan('restify:sanctum')->assertExitCode(Command::SUCCESS);

        $this->assertSame($userModelContent, File::get(app_path('Models/User.php')));
    }

    #[Test]
    public function it_fails_when_the_user_model_has_a_non_default_trait_list(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), "<?php\n\nreturn [\n    'middleware' => [\n        'api',\n    ],\n];\n");

        $userModelContent = <<<'PHP'
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Database\Eloquent\SoftDeletes;
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Illuminate\Notifications\Notifiable;

            class User extends Authenticatable
            {
                use HasFactory, Notifiable, SoftDeletes;
            }

            PHP;

        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/User.php'), $userModelContent);

        $this->artisan('restify:sanctum')
            ->expectsOutputToContain('Could not automatically add the HasApiTokens trait')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame($userModelContent, File::get(app_path('Models/User.php')));
    }

    #[Test]
    public function it_succeeds_when_the_auth_sanctum_middleware_is_already_present(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        $configContent = "<?php\n\nreturn [\n    'middleware' => [\n        'api',\n        'auth:sanctum',\n    ],\n];\n";
        File::put(config_path('restify.php'), $configContent);

        $this->seedValidUserModel();

        $this->artisan('restify:sanctum')
            ->expectsOutputToContain('The auth:sanctum middleware is already present in the middleware list.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($configContent, File::get(config_path('restify.php')));
    }

    #[Test]
    public function it_appends_auth_sanctum_middleware_after_the_api_entry_when_missing(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        File::put(config_path('restify.php'), "<?php\n\nreturn [\n    'middleware' => [\n        'api',\n    ],\n];\n");

        $this->seedValidUserModel();

        $this->artisan('restify:sanctum')->assertExitCode(Command::SUCCESS);

        $updatedContent = File::get(config_path('restify.php'));

        $this->assertStringContainsString("'api',\n        'auth:sanctum',", $updatedContent);
    }

    #[Test]
    public function it_fails_when_the_api_middleware_entry_is_missing(): void
    {
        File::put(base_path('composer.lock'), json_encode([
            'packages' => [['name' => 'laravel/sanctum']],
        ]));

        $configContent = "<?php\n\nreturn [\n    'middleware' => [\n        'web',\n    ],\n];\n";
        File::put(config_path('restify.php'), $configContent);

        $this->seedValidUserModel();

        $this->artisan('restify:sanctum')
            ->expectsOutputToContain('Could not find \'api\',')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame($configContent, File::get(config_path('restify.php')));
    }

    #[Test]
    public function it_treats_a_malformed_composer_lock_as_sanctum_not_installed(): void
    {
        File::put(base_path('composer.lock'), '{not valid json');

        $this->fakeFailingComposerBinary();

        $this->artisan('restify:sanctum')
            ->expectsOutputToContain('composer require failed')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_treats_a_composer_lock_with_no_packages_key_as_sanctum_not_installed(): void
    {
        File::put(base_path('composer.lock'), json_encode(['content-hash' => 'abc']));

        $this->fakeFailingComposerBinary();

        $this->artisan('restify:sanctum')
            ->expectsOutputToContain('composer require failed')
            ->assertExitCode(Command::FAILURE);
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

    private function fakeFailingComposerBinary(): void
    {
        $binPath = $this->tempBasePath.'/bin';
        File::ensureDirectoryExists($binPath);

        $composerStub = $binPath.'/composer';
        File::put($composerStub, "#!/bin/sh\necho 'composer require failed' >&2\nexit 1\n");
        chmod($composerStub, 0755);

        putenv("PATH={$binPath}:".getenv('PATH'));
    }
}
