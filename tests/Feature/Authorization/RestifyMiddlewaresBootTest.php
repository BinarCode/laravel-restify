<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authorization;

use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RestifyMiddlewaresBootTest extends IntegrationTest
{
    public function test_restify_loads_config_middleware(): void
    {
        $this->withoutExceptionHandling();

        $this->getJson(UserRepository::to())
            ->dump()
            ->assertOk();
    }
}
