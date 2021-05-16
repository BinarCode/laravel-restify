<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;

class RestifyServiceProvider extends RestifyApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the Restify gate.
     *
     * This gate determines who can access Restify in non-local environments.
     *
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewRestify', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
