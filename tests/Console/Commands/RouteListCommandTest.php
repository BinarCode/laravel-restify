<?php

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RouteListCommandTest extends IntegrationTest
{
    public function test_can_list_restify_routes(): void
    {
        // This is automatically registered in RestifyInjector middleware in a non console env mode.
        app(RoutesBoot::class)->boot();

        $this->artisan('route:list')->expectsOutputToContain("api/restify/{repository}");
    }
}
