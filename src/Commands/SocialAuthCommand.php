<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Socialite\SocialiteServiceProvider;

use function Laravel\Prompts\multiselect;

class SocialAuthCommand extends Command
{
    protected $signature = 'restify:social
        {--providers= : Comma-separated list of providers to scaffold (e.g. github,google,atlassian)}
        {--publish : Publish the controllers, resolver and model into the app for full customization}
        {--install : Run composer require for community providers (socialiteproviders/*)}
        {--no-routes : Skip appending Route::restifySocialAuth() to routes/api.php}
        {--no-env : Skip writing keys into .env / .env.example}
        {--no-services : Skip writing the provider blocks into config/services.php}';

    protected $description = 'Scaffold social (OAuth) authentication: migration, routes, env keys, services config and (optionally) publishable controllers.';

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

        if (! $this->option('no-env')) {
            $this->writeEnv($providers);
        }

        if (! $this->option('no-services')) {
            $this->writeServices($providers);
        }

        $this->installCommunityProviders($providers);

        $this->newLine();
        $this->components->info('Social auth scaffolded. Fill in the credentials in .env, then run `php artisan migrate`.');

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

    protected function writeEnv(array $providers): void
    {
        $filesystem = new Filesystem;

        foreach (['.env', '.env.example'] as $file) {
            $path = base_path($file);

            if (! $filesystem->exists($path)) {
                continue;
            }

            $updated = static::appendMissingEnv($filesystem->get($path), $providers);

            if ($updated === null) {
                $this->components->twoColumnDetail($file, '<fg=yellow>keys already present</>');

                continue;
            }

            $filesystem->put($path, $updated);
            $this->components->twoColumnDetail($file, '<fg=green>keys appended</>');
        }
    }

    protected function writeServices(array $providers): void
    {
        $filesystem = new Filesystem;
        $path = config_path('services.php');

        if (! $filesystem->exists($path)) {
            $this->components->twoColumnDetail('config/services.php', '<fg=yellow>missing — add the blocks shown above manually</>');
            $this->printServicesBlocks($providers);

            return;
        }

        $updated = static::injectServices($filesystem->get($path), $providers);

        if ($updated === null) {
            $this->components->twoColumnDetail('config/services.php', '<fg=yellow>providers already present</>');

            return;
        }

        $filesystem->put($path, $updated);
        $this->components->twoColumnDetail('config/services.php', '<fg=green>provider blocks added</>');
    }

    protected function installCommunityProviders(array $providers): void
    {
        $community = array_values(array_intersect($providers, $this->communityProviders));

        if (empty($community)) {
            return;
        }

        if (! $this->option('install')) {
            foreach ($community as $provider) {
                $this->components->warn("{$provider} needs a community driver: composer require socialiteproviders/{$provider} (see socialiteproviders.com)");
            }

            return;
        }

        foreach ($community as $provider) {
            $package = "socialiteproviders/{$provider}";
            $this->components->task("Installing {$package}", function () use ($package) {
                return Process::path(base_path())
                    ->timeout(300)
                    ->run("composer require {$package}")
                    ->successful();
            });
        }

        $this->components->warn('Register the community provider(s) per socialiteproviders.com (Event listener + service provider).');
    }

    protected function printServicesBlocks(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->line('  '.str_replace("\n", "\n  ", trim(static::servicesBlockFor($provider))));
        }
    }

    /**
     * The UPPER_SNAKE env prefix for a provider (e.g. "linkedin-openid" -> "LINKEDIN_OPENID").
     */
    public static function envPrefix(string $provider): string
    {
        return Str::of($provider)->upper()->replace('-', '_')->toString();
    }

    /**
     * The env keys a provider needs.
     */
    public static function envKeysFor(string $provider): array
    {
        $prefix = static::envPrefix($provider);

        return ["{$prefix}_CLIENT_ID", "{$prefix}_CLIENT_SECRET", "{$prefix}_REDIRECT_URI"];
    }

    /**
     * Append any missing provider env keys to the given .env contents.
     * Returns null when nothing changed (every key already present).
     */
    public static function appendMissingEnv(string $contents, array $providers): ?string
    {
        $additions = [];

        foreach ($providers as $provider) {
            [$id, $secret, $redirect] = static::envKeysFor($provider);

            // Skip providers whose keys are already declared.
            if (preg_match('/^\s*'.preg_quote($id, '/').'=/m', $contents)) {
                continue;
            }

            $additions[] = "{$id}=";
            $additions[] = "{$secret}=";
            $additions[] = "{$redirect}=\${APP_URL}/api/auth/social/{$provider}/callback";
        }

        if (empty($additions)) {
            return null;
        }

        return rtrim($contents)."\n\n# Restify social auth\n".implode("\n", $additions)."\n";
    }

    /**
     * The config/services.php array block for a single provider.
     */
    public static function servicesBlockFor(string $provider): string
    {
        $prefix = static::envPrefix($provider);

        return <<<PHP
        '{$provider}' => [
            'client_id' => env('{$prefix}_CLIENT_ID'),
            'client_secret' => env('{$prefix}_CLIENT_SECRET'),
            'redirect' => env('{$prefix}_REDIRECT_URI'),
        ],
    PHP;
    }

    /**
     * Inject any missing provider blocks before the final "];" of a services.php file.
     * Returns null when nothing changed (every provider already present).
     */
    public static function injectServices(string $contents, array $providers): ?string
    {
        $blocks = [];

        foreach ($providers as $provider) {
            if (preg_match("/['\"]".preg_quote($provider, '/')."['\"]\s*=>/", $contents)) {
                continue;
            }

            $blocks[] = static::servicesBlockFor($provider);
        }

        if (empty($blocks)) {
            return null;
        }

        $injection = "\n".implode("\n", $blocks)."\n";

        // Insert just before the array's closing "];" (the last one in the file).
        $position = strrpos($contents, '];');

        if ($position === false) {
            return rtrim($contents)."\n".$injection;
        }

        return substr($contents, 0, $position).$injection.substr($contents, $position);
    }
}
