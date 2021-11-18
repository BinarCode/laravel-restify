<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionException;
use RuntimeException;

class RestifyApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->authorization();
        $this->repositories();
    }

    /**
     * Register the application's Rest resources.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function repositories(): void
    {
        if ((false === is_dir(app_path('Restify'))) && ! mkdir($concurrentDirectory = app_path('Restify')) && ! is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        Restify::repositoriesFrom(app_path('Restify'));
    }

    /**
     * Configure the Restify authorization services.
     *
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        /*
         * Adding an auth callback. This callback will be verified in the AuthorizeRestify middleware,
         * which is the last middleware in the middleware list from the configuration.
         */
        Restify::auth(function ($request) {
            return app()->environment('local') ||
                Gate::check('viewRestify', [$request->user()]);
        });
    }

    /**
     * Register the Restify gate.
     *
     * This gate determines who can access Restify in non-local environments.
     *
     * This gate is checked in `authorization` method above and it should be overrided in the child
     * service provider
     *
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewRestify', function ($user) {
            return in_array($user->email, [
                //
            ], true);
        });
    }
}
