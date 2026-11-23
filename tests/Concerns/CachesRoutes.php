<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @mixin TestCase
 */
trait CachesRoutes
{
    /**
     * Swap the router's routes for their cached form, built the way `artisan route:cache` builds it: every route
     * prepared for serialization, the collection compiled, the result exported to a PHP file and loaded back from
     * that file. The command itself cannot run here, as it boots a second application from `bootstrap/app.php`,
     * which under Testbench is a skeleton that knows none of the repositories a test registers.
     *
     * By default nothing registers a route afterwards, so a request is answered by a cached route or by none.
     * Pass false to keep Restify registering its routes on each request next to the cached ones, as it does in an
     * application.
     */
    protected function cacheRoutes(bool $cachedRoutesOnly = true): void
    {
        // The service provider does this when artisan boots the application.
        app(RoutesBoot::class)->boot();

        $routes = Route::getRoutes();

        $this->assertInstanceOf(RouteCollection::class, $routes);

        $routes->refreshNameLookups();
        $routes->refreshActionLookups();

        foreach ($routes as $route) {
            $route->prepareForSerialization();
        }

        $file = tempnam(sys_get_temp_dir(), 'restify-routes-');

        $this->assertIsString($file);

        try {
            file_put_contents($file, '<?php return '.var_export($routes->compile(), true).';');

            Route::setCompiledRoutes(require $file);
        } finally {
            unlink($file);
        }

        if ($cachedRoutesOnly) {
            $this->app->bind(RoutesBoot::class, fn () => new class extends RoutesBoot
            {
                public function boot(): void {}
            });
        }
    }
}
