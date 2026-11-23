<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Concerns\CachesRoutes;

/**
 * Every guarantee of the parent, with the routes served from the route cache. A cached route is rebuilt from its
 * exported action, so the repository a prefixed or public route was registered for, and the middleware a public
 * route leaves out, have to survive that round trip.
 */
class RepositoryResolvedFromCachedRouteTest extends RepositoryResolvedFromRouteTest
{
    use CachesRoutes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheRoutes();
    }
}
