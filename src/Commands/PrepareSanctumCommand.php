<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PrepareSanctumCommand extends Command
{
    protected $signature = 'restify:sanctum';

    protected $description = 'Uncomment or add if missing the auth:sanctum middleware to the middleware list.';

    public function handle(): int
    {
        $this->info('Prepare Sanctum for Restify...');

        if (! $this->ensureSanctumIsInstalled()) {
            return self::FAILURE;
        }

        if (! $this->ensureUserHasApiTokensTrait()) {
            return self::FAILURE;
        }

        return $this->replaceMiddleware();
    }

    protected function ensureSanctumIsInstalled(): bool
    {
        $composerLockPath = base_path('composer.lock');

        if (! File::exists($composerLockPath)) {
            $this->error('The composer.lock file does not exist. Run `composer install` first.');

            return false;
        }

        /** @var array{packages?: array<int, array{name?: string}>} $installedPackages */
        $installedPackages = json_decode(File::get($composerLockPath), true) ?? [];

        $sanctumInstalled = false;
        foreach ($installedPackages['packages'] ?? [] as $package) {
            if (($package['name'] ?? null) === 'laravel/sanctum') {
                $sanctumInstalled = true;
                break;
            }
        }

        if (! $sanctumInstalled) {
            $this->info('Laravel Sanctum is not installed. Installing now...');
            $this->runProcess(['composer', 'require', 'laravel/sanctum']);
            $this->runProcess(['php', 'artisan', 'vendor:publish', '--provider="Laravel\Sanctum\SanctumServiceProvider"']);
            $this->runProcess(['php', 'artisan', 'migrate']);
            $this->info('Laravel Sanctum has been installed.');
        } else {
            $this->info('Laravel Sanctum is already installed.');
        }

        return true;
    }

    protected function replaceMiddleware(): int
    {
        $configPath = config_path('restify.php');

        if (! File::exists($configPath)) {
            $this->error('The config/restify.php file does not exist.');

            return self::FAILURE;
        }

        $content = File::get($configPath);

        $pattern = '/\/\/\s*\'auth:sanctum\',/';
        $replacement = '        \'auth:sanctum\',';

        $updatedContent = preg_replace($pattern, $replacement, $content) ?? $content;

        if ($updatedContent === $content) {
            // Check if 'auth:sanctum' is already present in the middleware list
            if (! str_contains($content, '\'auth:sanctum\',')) {
                $apiMiddlewarePattern = "/'api',/";
                $replacement = "'api',\n        'auth:sanctum',";
                $updatedContent = preg_replace($apiMiddlewarePattern, $replacement, $content) ?? $content;
                File::put($configPath, $updatedContent);
                $this->info('The auth:sanctum middleware has been added to the middleware list.');
            } else {
                $this->info('The auth:sanctum middleware is already present in the middleware list.');
            }

            return self::SUCCESS;
        }

        File::put($configPath, $updatedContent);
        $this->info('The auth:sanctum comment has been replaced.');

        return self::SUCCESS;
    }

    protected function runProcess(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    protected function ensureUserHasApiTokensTrait(): bool
    {
        $userModelPath = app_path('Models/User.php');

        if (! File::exists($userModelPath)) {
            $this->error('The User model does not exist.');

            return false;
        }

        $content = File::get($userModelPath);

        if (strpos($content, 'use HasApiTokens;') !== false) {
            $this->info('The User model already uses the HasApiTokens trait.');

            return true;
        }

        if (strpos($content, 'use Laravel\Sanctum\HasApiTokens;') === false) {
            $useStatements = "use Laravel\Sanctum\HasApiTokens;\nuse Illuminate\Notifications\Notifiable;";
            $content = str_replace('use Illuminate\Notifications\Notifiable;', $useStatements, $content);
        }

        $content = str_replace('use HasFactory, Notifiable;', 'use HasFactory, Notifiable, HasApiTokens;', $content);

        File::put($userModelPath, $content);
        $this->info('The HasApiTokens trait has been added to the User model.');

        return true;
    }
}
