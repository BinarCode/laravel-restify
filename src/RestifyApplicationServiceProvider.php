<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class RestifyApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->authorization();
        $this->registerExceptionHandler();
        $this->repositories();
    }

    /**
     * Register the application's Rest resources.
     *
     * @return void
     */
    protected function repositories()
    {
        Restify::repositoriesFrom(app_path('Restify'));
    }

    /**
     * Register Restify's custom exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        if (config('restify.exception_handler') && class_exists(value(config('restify.exception_handler')))) {
            $this->app->bind(ExceptionHandler::class, value(config('restify.exception_handler')));
        }
    }

    /**
     * Configure the Restify authorization services.
     *
     * @return void
     */
    protected function authorization()
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
    protected function gate()
    {
        Gate::define('viewRestify', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
