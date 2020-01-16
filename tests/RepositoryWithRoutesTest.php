<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\RepositoryWithRoutes;
use Illuminate\Routing\Router;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryWithRoutesTest extends IntegrationTest
{
    protected function setUp(): void
    {
        $this->loadRepositories();

        Restify::repositories([
            WithCustomPrefix::class,
            WithCustomMiddleware::class,
            WithCustomNamespace::class,
        ]);

        parent::setUp();
    }

    public function test_can_add_custom_routes()
    {
        $this->get(Restify::path(RepositoryWithRoutes::uriKey()).'/testing')->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->get(route('testing.route'))->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_use_custom_prefix()
    {
        $this->get('/custom-prefix/testing')->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_use_custom_middleware()
    {
        $this->get(route('middleware.failing.route'))->assertStatus(403);
    }

    public function test_can_use_custom_namespace()
    {
        $this->getJson(route('namespace.route'))
            ->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'message' => 'From the sayHello method',
                ],
            ]);
    }
}

class WithCustomPrefix extends RepositoryWithRoutes
{
    public static function routes(Router $router, $options = ['prefix' => 'custom-prefix'])
    {
        $router->get('testing', function () {
            return response()->json([
                'success' => true,
            ]);
        })->name('custom.testing.route');
    }
}

class MiddlewareFail
{
    public function handle($request, $next)
    {
        if (true) {
            return abort(403);
        }
    }
}

class WithCustomMiddleware extends RepositoryWithRoutes
{
    public static function routes(Router $router, $options = ['middleware' => [MiddlewareFail::class]])
    {
        $router->get('with-middleware', function () {
            return response()->json([
                'success' => true,
            ]);
        })->name('middleware.failing.route');
    }
}

class WithCustomNamespace extends RepositoryWithRoutes
{
    public static function routes(Router $router, $options = [
        'namespace' => 'Binaryk\LaravelRestify\Tests',
    ])
    {
        $router->get('custom-namespace', 'HandleController@sayHello')->name('namespace.route');
    }
}

class HandleController extends RestController
{
    /**
     * Just saying hello.
     *
     * @return \Binaryk\LaravelRestify\Controllers\RestResponse
     */
    public function sayHello()
    {
        return $this->response()->message('From the sayHello method');
    }
}
