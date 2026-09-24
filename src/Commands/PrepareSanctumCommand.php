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

            try {
                $this->runProcess(['composer', 'require', 'laravel/sanctum']);
            } catch (ProcessFailedException $exception) {
                $this->error($exception->getMessage());

                return false;
            }

            $this->runProcess(['php', 'artisan', 'vendor:publish', '--provider=Laravel\Sanctum\SanctumServiceProvider']);
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

        $commentPattern = '/\/\/\s*\'auth:sanctum\',/';
        $commentReplacement = '        \'auth:sanctum\',';

        $updatedContent = preg_replace($commentPattern, $commentReplacement, $content) ?? $content;

        if ($updatedContent !== $content) {
            File::put($configPath, $updatedContent);
            $this->info('The auth:sanctum comment has been replaced.');

            return self::SUCCESS;
        }

        if (str_contains($content, '\'auth:sanctum\',')) {
            $this->info('The auth:sanctum middleware is already present in the middleware list.');

            return self::SUCCESS;
        }

        $apiMiddlewarePattern = "/'api',/";
        $apiMiddlewareReplacement = "'api',\n        'auth:sanctum',";
        $updatedContent = preg_replace($apiMiddlewarePattern, $apiMiddlewareReplacement, $content) ?? $content;

        if ($updatedContent === $content) {
            $this->error('Could not find \'api\', in the config/restify.php middleware list. Add \'auth:sanctum\', to the middleware array manually.');

            return self::FAILURE;
        }

        File::put($configPath, $updatedContent);
        $this->info('The auth:sanctum middleware has been added to the middleware list.');

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

        if ($this->userModelUsesApiTokensTrait($content)) {
            $this->info('The User model already uses the HasApiTokens trait.');

            return true;
        }

        $updatedContent = $content;

        if (! str_contains($updatedContent, 'use Laravel\Sanctum\HasApiTokens;')) {
            $useStatements = "use Laravel\Sanctum\HasApiTokens;\nuse Illuminate\Notifications\Notifiable;";
            $updatedContent = str_replace('use Illuminate\Notifications\Notifiable;', $useStatements, $updatedContent);
        }

        $beforeTraitRewrite = $updatedContent;
        $updatedContent = str_replace('use HasFactory, Notifiable;', 'use HasFactory, Notifiable, HasApiTokens;', $updatedContent);

        if ($updatedContent === $beforeTraitRewrite) {
            $this->error('Could not automatically add the HasApiTokens trait to the User model: the "use HasFactory, Notifiable;" trait statement was not found. Add "use Laravel\Sanctum\HasApiTokens;" to the imports and include HasApiTokens in the class\'s trait use statement manually.');

            return false;
        }

        File::put($userModelPath, $updatedContent);
        $this->info('The HasApiTokens trait has been added to the User model.');

        return true;
    }

    /**
     * Detects `HasApiTokens` in the class's own trait `use` list - as opposed
     * to a `use Laravel\Sanctum\HasApiTokens;` import line, which only brings
     * the trait into scope without applying it to the class. A class-body
     * trait list never contains a namespace separator, so that's what tells
     * the two kinds of `use` statement apart.
     */
    private function userModelUsesApiTokensTrait(string $content): bool
    {
        if (! preg_match_all('/^\s*use\s+([^;]+);/m', $content, $matches)) {
            return false;
        }

        foreach ($matches[1] as $traitList) {
            if (str_contains($traitList, '\\')) {
                continue;
            }

            $traits = array_map('trim', explode(',', $traitList));

            if (in_array('HasApiTokens', $traits, true)) {
                return true;
            }
        }

        return false;
    }
}
