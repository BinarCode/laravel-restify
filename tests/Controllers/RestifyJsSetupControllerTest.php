<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RestifyJsSetupControllerTest extends IntegrationTestCase
{
    public function test_returns_configurations(): void
    {
        $this
            ->withoutExceptionHandling()
            ->getJson(Restify::path('restifyjs/setup'))
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
