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

    public function handle()
    {
        $this->info('Prepare Sanctum for Restify...');

        $this->ensureSanctumIsInstalled();
        $this->ensureUserHasApiTokensTrait();

        $this->replaceMiddleware();

        return 0;
    }

    protected function ensureSanctumIsInstalled()
    {
        $installedPackages = json_decode(File::get(base_path('composer.lock')), true);

        $sanctumInstalled = false;
        foreach ($installedPackages['packages'] as $package) {
            if ($package['name'] === 'laravel/sanctum') {
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
    }

    protected function replaceMiddleware(): int
    {
        $configPath = config_path('restify.php');

        if (! File::exists($configPath)) {
            $this->error('The config/restify.php file does not exist.');

            return 1;
        }

        $content = File::get($configPath);

        $pattern = '/\/\/\s*\'auth:sanctum\',/';
        $replacement = '        \'auth:sanctum\',';

        $updatedContent = preg_replace($pattern, $replacement, $content);

        if ($updatedContent === $content) {
            // Check if 'auth:sanctum' is already present in the middleware list
            if (strpos($content, '\'auth:sanctum\',') === false) {
                $apiMiddlewarePattern = "/'api',/";
                $replacement = "'api',\n        'auth:sanctum',";
                $updatedContent = preg_replace($apiMiddlewarePattern, $replacement, $content);
                File::put($configPath, $updatedContent);
                $this->info('The auth:sanctum middleware has been added to the middleware list.');
            } else {
                $this->info('The auth:sanctum middleware is already present in the middleware list.');
            }

            return 0;
        }

        File::put($configPath, $updatedContent);
        $this->info('The auth:sanctum comment has been replaced.');

        return 1;
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

    protected function ensureUserHasApiTokensTrait()
    {
        $userModelPath = app_path('Models/User.php');

        if (! File::exists($userModelPath)) {
            $this->error('The User model does not exist.');

            return;
        }

        $content = File::get($userModelPath);

        // Check if HasApiTokens trait is already used
        if (strpos($content, 'use HasApiTokens;') !== false) {
            $this->info('The User model already uses the HasApiTokens trait.');

            return;
        }

        // Check if HasApiTokens is already imported
        if (strpos($content, 'use Laravel\Sanctum\HasApiTokens;') === false) {
            // Import HasApiTokens trait
            $useStatements = "use Laravel\Sanctum\HasApiTokens;\nuse Illuminate\Notifications\Notifiable;";
            $content = str_replace('use Illuminate\Notifications\Notifiable;', $useStatements, $content);
        }

        // Add HasApiTokens trait to the User class
        $content = str_replace('use HasFactory, Notifiable;', 'use HasFactory, Notifiable, HasApiTokens;', $content);

        File::put($userModelPath, $content);
        $this->info('The HasApiTokens trait has been added to the User model.');
    }
}
