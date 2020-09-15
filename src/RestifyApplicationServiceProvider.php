<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Controllers\AuthController;
use Binaryk\LaravelRestify\Http\Middleware\EnsureJsonApiHeaderMiddleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RestifyApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->authorization();
        $this->repositories();
        $this->authRoutes();
    }

    /**
     * Register the application's Rest resources.
     *
     * @return void
     */
    protected function repositories()
    {
        if (false === is_dir(app_path('Restify'))) {
            mkdir(app_path('Restify'));
        }

        Restify::repositoriesFrom(app_path('Restify'));
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

    protected function authRoutes()
    {
        Route::macro('restifyAuth', function ($prefix = '/') {
            Route::group([
                'prefix' => $prefix,
                'middleware' => [EnsureJsonApiHeaderMiddleware::class],
            ], function() {
                Route::post('register', [AuthController::class, 'register'])
                    ->name('restify.register');

                Route::post('login', [AuthController::class, 'login'])
                    ->middleware('throttle:6,1')
                    ->name('restify.login');

                Route::post('verify/{id}/{hash}', [AuthController::class, 'verify'])
                    ->middleware('throttle:6,1')
                    ->name('restify.verify');

                Route::post('forgotPassword', [AuthController::class, 'forgotPassword'])
                    ->middleware('throttle:6,1')
                    ->name('restify.forgotPassword');

                Route::post('resetPassword', [AuthController::class, 'resetPassword'])
                    ->middleware('throttle:6,1')
                    ->name('restify.resetPassword');
            });
        });
    }
}
