<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Controllers\Auth\ForgotPasswordController;
use Binaryk\LaravelRestify\Http\Controllers\Auth\LoginController;
use Binaryk\LaravelRestify\Http\Controllers\Auth\RegisterController;
use Binaryk\LaravelRestify\Http\Controllers\Auth\ResetPasswordController;
use Binaryk\LaravelRestify\Http\Controllers\Auth\VerifyController;
use Binaryk\LaravelRestify\Http\Middleware\RestifyInjector;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionException;

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
        $this->routes();
        $this->singleton();
    }

    /**
     * Register the application's Rest resources.
     *
     *
     * @throws ReflectionException
     */
    protected function repositories(): void
    {
        Restify::repositoriesFrom(app_path('Restify'), app()->getNamespace());
    }

    /**
     * Configure the Restify authorization services.
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
     */
    protected function gate(): void
    {
        Gate::define('viewRestify', function ($user = null) {
            return in_array($user->email, [
                //
            ], true);
        });
    }

    protected function authRoutes(): void
    {
        Route::macro('restifyAuth', function ($prefix = '/', array $actions = ['register', 'login', 'verifyEmail', 'forgotPassword', 'resetPassword']) {
            Route::group([
                'prefix' => $prefix,
                'middleware' => ['api'],
            ], function () use ($actions) {
                if (in_array('register', $actions, true)) {
                    Route::post('register', RegisterController::class)
                        ->name('restify.register');
                }

                if (in_array('login', $actions, true)) {
                    Route::post('login', LoginController::class)
                        ->middleware('throttle:6,1')
                        ->name('restify.login');
                }

                if (in_array('verifyEmail', $actions, true)) {
                    Route::post('verify/{id}/{hash}', VerifyController::class)
                        ->middleware('throttle:6,1')
                        ->name('restify.verify');
                }

                if (in_array('forgotPassword', $actions, true)) {
                    Route::post('forgotPassword', ForgotPasswordController::class)
                        ->middleware('throttle:6,1')
                        ->name('restify.forgotPassword');
                }

                if (in_array('resetPassword', $actions, true)) {
                    Route::post('resetPassword', ResetPasswordController::class)
                        ->middleware('throttle:6,1')
                        ->name('restify.resetPassword');
                }
            });
        });
    }

    protected function routes(): void
    {
        /**
         * @var Kernel $kernel
         */
        $kernel = $this->app->make(Kernel::class);

        $kernel->pushMiddleware(RestifyInjector::class);

        // List routes when running artisan route:list
        if (App::runningInConsole() && ! App::runningUnitTests()) {
            app(RoutesBoot::class)->boot();
        }
    }

    protected function singleton(): void
    {
        if (! App::runningUnitTests()) {
            $this->app->singletonIf(RelatedDto::class, fn ($app) => new RelatedDto());
        }
    }
}
