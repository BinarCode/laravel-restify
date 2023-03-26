<?php

namespace CustomNamespace\PackageA;

use Binaryk\LaravelRestify\Traits\InteractsWithRestifyRepositories;
use Illuminate\Support\ServiceProvider;

class PackageAServiceProvider extends ServiceProvider
{
    use InteractsWithRestifyRepositories;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRestifyFrom(__DIR__.'/Restify', __NAMESPACE__.'\\Restify\\');
    }
}
