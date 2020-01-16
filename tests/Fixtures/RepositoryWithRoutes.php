<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Routing\Router;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryWithRoutes extends Repository
{
    public static function routes(Router $router, $options = [])
    {
        $router->get('testing', function () {
            return response()->json([
                'success' => true,
            ]);
        })->name('testing.route');
    }

    public static function uriKey()
    {
        return 'posts';
    }
}
