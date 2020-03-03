<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restify:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare Restify dependencies and resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Restify Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'restify-provider']);

        $this->comment('Publishing Restify config...');
        $this->call('vendor:publish', [
            '--tag' => 'restify-config',
        ]);

        $this->registerRestifyServiceProvider();

        $this->comment('Generating User Repository...');
        $this->callSilent('restify:repository', ['name' => 'User']);
        copy(__DIR__.'/stubs/user-repository.stub', app_path('Restify/User.php'));

        $this->setAppNamespace();

        $this->info('Restify setup successfully.');
    }

    /**
     * Register the Restify service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerRestifyServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\RestifyServiceProvider::class,".PHP_EOL,
            file_get_contents(config_path('app.php'))
        ));
    }

    /**
     * Set the proper application namespace on the installed files.
     *
     * @return void
     */
    protected function setAppNamespace()
    {
        $namespace = $this->laravel->getNamespace();

        $this->setAppNamespaceOn(app_path('Restify/User.php'), $namespace);
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
