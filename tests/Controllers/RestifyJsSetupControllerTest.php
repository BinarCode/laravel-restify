<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RestifyJsSetupControllerTest extends IntegrationTest
{
    public function test_returns_configurations()
    {
        $this->getJson('restifyjs/setup')
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
                    ]
                ]
            ])
            ->assertOk();
    }
}
