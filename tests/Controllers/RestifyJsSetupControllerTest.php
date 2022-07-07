<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RestifyJsSetupControllerTest extends IntegrationTest
{
    public function test_returns_configurations(): void
    {
        $this->getJson(Restify::path('restifyjs/setup'))
            ->assertJsonStructure([
                'config' => [
                    'domain',
                    'base',
                ],
                'repositories' => [
                    [
                        'uriKey',
                        'related',
                        'sort',
                        'match',
                        'searchables',
                        'actions',
                    ],
                ],
            ])
            ->assertOk();
    }
}
