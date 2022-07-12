<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Binaryk\LaravelRestify\Commands\ActionCommand;
use Binaryk\LaravelRestify\Commands\BaseRepositoryCommand;
use Binaryk\LaravelRestify\Commands\DevCommand;
use Binaryk\LaravelRestify\Commands\FilterCommand;
use Binaryk\LaravelRestify\Commands\GetterCommand;
use Binaryk\LaravelRestify\Commands\PolicyCommand;
use Binaryk\LaravelRestify\Commands\PublishAuthCommand;
use Binaryk\LaravelRestify\Commands\Refresh;
use Binaryk\LaravelRestify\Commands\RepositoryCommand;
use Binaryk\LaravelRestify\Commands\SetupCommand;
use Binaryk\LaravelRestify\Commands\StoreCommand;
use Binaryk\LaravelRestify\Commands\StubCommand;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Middleware\RestifyInjector;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\App;
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
            ]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }

        /**
         * @var Kernel $kernel
         */
        $kernel = $this->app->make(Kernel::class);

        $kernel->pushMiddleware(RestifyInjector::class);

        if (! App::runningUnitTests()) {
            app(RoutesBoot::class)->boot();
        }

        $this->app->singleton(RelatedDto::class, fn($app) => new RelatedDto());
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
