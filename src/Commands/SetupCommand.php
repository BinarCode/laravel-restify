<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    protected $signature = 'restify:setup';

    protected $description = 'Should be run when you firstly install the package. It will setup everything for you.';

    public function handle()
    {
        $this->comment('Installing Laravel API...');
        $this->call('install:api');

        $this->comment('Publishing Restify Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'restify-provider']);

        $this->comment('Publishing Restify config...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-config',
        ]);

        $this->comment('Publishing Restify migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-migrations',
        ]);

        $this->comment('Publishing Restify views...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-views',
        ]);

        $this->registerRestifyServiceProvider();

        $this->comment('Generating User Repository...');
        $this->call(RepositoryCommand::class, ['name' => 'UserRepository']);

        if (! file_exists(app_path('Policies/UserPolicy.php'))) {
            app(Filesystem::class)->ensureDirectoryExists(app_path('Policies'));
            $this->call(PolicyCommand::class, ['name' => 'UserPolicy']);
        }

        $this->setAppNamespace();

        $this->info('Restify setup successfully.');
    }

    /**
     * Register the Restify service provider in the bootstrap/providers.php file.
     *
     * @return void
     */
    protected function registerRestifyServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $providerClass = "{$namespace}\\Providers\\RestifyServiceProvider::class";

        $providersPath = base_path('bootstrap/providers.php');

        // Check if the providers.php file exists
        if (! file_exists($providersPath)) {
            $this->error('bootstrap/providers.php file not found. Make sure you are using Laravel 12.');

            return;
        }

        $content = file_get_contents($providersPath);

        // Check if the provider is already registered
        if (str_contains($content, $providerClass)) {
            $this->line('RestifyServiceProvider already registered.');

            return;
        }

        // Find the return statement
        $pattern = '/return\s+(\[.*?\]);/s';
        if (preg_match($pattern, $content, $matches)) {
            $providersArray = $matches[1];

            // Remove the closing bracket
            $providersArrayWithoutClosing = rtrim(trim($providersArray), ']');

            // Add our provider and close the array
            $newProvidersArray = $providersArrayWithoutClosing;

            // If the array is not empty and doesn't end with a comma, add a comma
            if (! empty($providersArrayWithoutClosing) && ! str_ends_with(trim($providersArrayWithoutClosing), ',')) {
                $newProvidersArray .= ',';
            }

            $newProvidersArray .= "\n    {$providerClass},\n]";

            // Replace the old array with the new one
            $newContent = preg_replace($pattern, "return {$newProvidersArray};", $content);

            file_put_contents($providersPath, $newContent);

            $this->line('RestifyServiceProvider registered in bootstrap/providers.php');
        } else {
            $this->error('Could not find the providers array in bootstrap/providers.php');
        }
    }

    /**
     * Set the proper application namespace on the installed files.
     *
     * @return void
     */
    protected function setAppNamespace()
    {
        $namespace = $this->laravel->getNamespace();

        $this->setAppNamespaceOn(app_path('Restify/UserRepository.php'), $namespace);
        $this->setAppNamespaceOn(app_path('Providers/RestifyServiceProvider.php'), $namespace);
    }

    /**
     * Set the namespace on the given file.
     *
     * @param  string  $file
     * @param  string  $namespace
     * @return void
     */
    protected function setAppNamespaceOn($file, $namespace)
    {
        file_put_contents($file, str_replace(
            'App\\',
            $namespace,
            file_get_contents($file)
        ));
    }
}
