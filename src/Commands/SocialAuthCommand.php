<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Socialite\SocialiteServiceProvider;

use function Laravel\Prompts\multiselect;

class SocialAuthCommand extends Command
{
    protected $signature = 'restify:social
        {--providers= : Comma-separated list of providers to scaffold (e.g. github,google,atlassian)}
        {--publish : Publish the controllers, resolver and model into the app for full customization}
        {--no-routes : Skip appending Route::restifySocialAuth() to routes/api.php}';

    protected $description = 'Scaffold social (OAuth) authentication: migration, routes, env keys and (optionally) publishable controllers.';

    /**
     * Drivers that ship with Laravel Socialite out of the box vs. community ones.
     */
    protected array $coreProviders = ['github', 'google', 'facebook', 'gitlab', 'bitbucket', 'linkedin-openid', 'twitter-oauth-2'];

    protected array $communityProviders = ['atlassian', 'jira', 'discord', 'slack', 'microsoft', 'apple'];

    public function handle(): int
    {
        if (! class_exists(SocialiteServiceProvider::class)) {
            $this->components->error('Laravel Socialite is required for social auth.');
            $this->components->info('Install it with: composer require laravel/socialite');

            return self::FAILURE;
        }

        $providers = $this->resolveProviders();

        $this->publishMigration();

        if ($this->option('publish')) {
            $this->publishStubs();
        }

        if (! $this->option('no-routes')) {
            $this->registerRoute();
        }

        $this->printProviderInstructions($providers);

        $this->newLine();
        $this->components->info('Social auth scaffolded. Run `php artisan migrate` once your env is set.');

        return self::SUCCESS;
    }

    protected function resolveProviders(): array
    {
        if ($option = $this->option('providers')) {
            return array_values(array_filter(array_map('trim', explode(',', $option))));
        }

        $configured = array_keys((array) config('restify.auth.social.providers', []));

        if (! $this->input->isInteractive()) {
            return $configured ?: ['github'];
        }

        return multiselect(
            label: 'Which providers do you want to enable?',
            options: array_values(array_unique(array_merge($this->coreProviders, $this->communityProviders, $configured))),
            default: $configured ?: ['github'],
            hint: 'Core drivers ship with Socialite; community ones need a socialiteproviders.com package.',
        );
    }

    protected function publishMigration(): void
    {
        $filesystem = new Filesystem;
        $stub = __DIR__.'/../../database/migrations/create_social_accounts_table.php.stub';

        $existing = collect($filesystem->glob(database_path('migrations/*_create_social_accounts_table.php')));

        if ($existing->isNotEmpty()) {
            $this->components->twoColumnDetail('Migration', '<fg=yellow>already exists, skipped</>');

            return;
        }

        $target = database_path('migrations/'.date('Y_m_d_His').'_create_social_accounts_table.php');
        $filesystem->ensureDirectoryExists(dirname($target));
        $filesystem->copy($stub, $target);

        $this->components->twoColumnDetail('Migration', '<fg=green>'.basename($target).'</>');
    }

    protected function publishStubs(): void
    {
        $filesystem = new Filesystem;

        $map = [
            'stubs/Social/SocialRedirectController.stub' => app_path('Http/Controllers/Restify/Auth/Social/SocialRedirectController.php'),
            'stubs/Social/SocialCallbackController.stub' => app_path('Http/Controllers/Restify/Auth/Social/SocialCallbackController.php'),
            'stubs/Social/SocialUserResolver.stub' => app_path('Restify/Social/SocialUserResolver.php'),
        ];

        foreach ($map as $stub => $target) {
            if ($filesystem->exists($target)) {
                $this->components->twoColumnDetail(basename($target), '<fg=yellow>exists, skipped</>');

                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($target));

            $contents = str_replace(
                '{{namespace}}',
                $this->namespaceFor($target),
                $filesystem->get(__DIR__.'/'.$stub)
            );

            $filesystem->put($target, $contents);
            $this->components->twoColumnDetail(basename($target), '<fg=green>published</>');
        }

        $this->components->warn('Point restify.auth.social.controllers / .resolver / .model at your published classes to use them.');
    }

    protected function namespaceFor(string $target): string
    {
        $relative = Str::of($target)
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->beforeLast(DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '\\');

        return rtrim($this->laravel->getNamespace(), '\\').'\\'.$relative;
    }

    protected function registerRoute(): void
    {
        $apiPath = base_path('routes/api.php');
        $filesystem = new Filesystem;

        if (! $filesystem->exists($apiPath)) {
            $this->components->twoColumnDetail('routes/api.php', '<fg=yellow>missing — add Route::restifySocialAuth() manually</>');

            return;
        }

        $contents = $filesystem->get($apiPath);

        if (Str::contains($contents, 'Route::restifySocialAuth')) {
            $this->components->twoColumnDetail('routes/api.php', '<fg=yellow>already registered</>');

            return;
        }

        $filesystem->put($apiPath, rtrim($contents)."\n\nRoute::restifySocialAuth();\n");
        $this->components->twoColumnDetail('routes/api.php', '<fg=green>Route::restifySocialAuth() appended</>');
    }

    protected function printProviderInstructions(array $providers): void
    {
        $this->newLine();
        $this->components->info('Add these to your .env:');

        foreach ($providers as $provider) {
            $const = Str::of($provider)->upper()->replace('-', '_')->toString();
            $this->line("  <fg=cyan>{$const}_CLIENT_ID</>=");
            $this->line("  <fg=cyan>{$const}_CLIENT_SECRET</>=");
            $this->line("  <fg=cyan>{$const}_REDIRECT_URI</>=\${APP_URL}/api/auth/social/{$provider}/callback");
        }

        $this->newLine();
        $this->components->info('Add these to config/services.php:');

        foreach ($providers as $provider) {
            $const = Str::of($provider)->upper()->replace('-', '_')->toString();
            $this->line("  '{$provider}' => [");
            $this->line("      'client_id' => env('{$const}_CLIENT_ID'),");
            $this->line("      'client_secret' => env('{$const}_CLIENT_SECRET'),");
            $this->line("      'redirect' => env('{$const}_REDIRECT_URI'),");
            $this->line('  ],');

            if (in_array($provider, $this->communityProviders, true)) {
                $this->line("  <fg=yellow># {$provider} needs: composer require socialiteproviders/{$provider} (see socialiteproviders.com)</>");
            }
        }
    }
}
