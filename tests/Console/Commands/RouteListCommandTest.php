<?php

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RouteListCommandTest extends IntegrationTestCase
{
    public function test_can_list_restify_routes(): void
    {
        // This is automatically registered in RestifyInjector middleware in a non console env mode.
        app(RoutesBoot::class)->boot();

        if (method_exists($command = $this->artisan('route:list'), 'expectsOutputToContain')) {
            $command->expectsOutputToContain('api/restify/{repository}');
        }
    }
}
