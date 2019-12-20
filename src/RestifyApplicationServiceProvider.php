<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Events\RestifyServing;
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
        /*
         * At the end of the middleware stacks from the config, we dispatch the RestifyServing
         * event, and this is the callback happening after the last middleware passed.
         */
        Restify::serving(function (RestifyServing $event) {
            $this->authorization();
            $this->registerExceptionHandler();
            $this->repositories();
        });
    }

    /**
     * Register the application's Rest resources.
     *
     * @return void
     * @throws \ReflectionException
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
        $this->app->bind(ExceptionHandler::class, RestifyHandler::class);
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
     * This gate is checked in `authorization` method above
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
