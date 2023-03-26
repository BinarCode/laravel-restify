<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Commands\ActionCommand;
use Binaryk\LaravelRestify\Commands\BaseRepositoryCommand;
use Binaryk\LaravelRestify\Commands\DevCommand;
use Binaryk\LaravelRestify\Commands\FilterCommand;
use Binaryk\LaravelRestify\Commands\GetterCommand;
use Binaryk\LaravelRestify\Commands\PolicyCommand;
use Binaryk\LaravelRestify\Commands\PrepareSanctumCommand;
use Binaryk\LaravelRestify\Commands\PublishAuthCommand;
use Binaryk\LaravelRestify\Commands\Refresh;
use Binaryk\LaravelRestify\Commands\RepositoryCommand;
use Binaryk\LaravelRestify\Commands\RestifyRouteListCommand;
use Binaryk\LaravelRestify\Commands\SetupAuthCommand;
use Binaryk\LaravelRestify\Commands\SetupCommand;
use Binaryk\LaravelRestify\Commands\StoreCommand;
use Binaryk\LaravelRestify\Commands\StubCommand;
use Binaryk\LaravelRestify\Repositories\Repository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRestifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-restify')
            ->hasConfigFile()
            ->hasMigration('create_action_logs_table')
            ->runsMigrations()
            ->hasCommands([
                RepositoryCommand::class,
                ActionCommand::class,
                GetterCommand::class,
                StoreCommand::class,
                FilterCommand::class,
                DevCommand::class,
                SetupCommand::class,
                PolicyCommand::class,
                BaseRepositoryCommand::class,
                Refresh::class,
                StubCommand::class,
                PublishAuthCommand::class,
                RestifyRouteListCommand::class,
                PrepareSanctumCommand::class,
                SetupAuthCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    public function packageRegistered(): void
    {
        Repository::clearBootedRepositories();

        // Register the main class to use with the facade
        $this->app->singleton('laravel-restify', function () {
            return new Restify();
        });
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/Commands/stubs/RestifyServiceProvider.stub' => app_path('Providers/RestifyServiceProvider.php'),
        ], 'restify-provider');
    }
}
