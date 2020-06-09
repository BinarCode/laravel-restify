<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryAttachInterceptorTest extends IntegrationTest
{
    public function test_can_intercept_attach_method()
    {
        $role = factory(Role::class)->create();
        $user = $this->mockUsers()->first();

        $this->postJson('restify-api/roles/'.$role->id.'/attach/users', [
            'users' => $user->id,
        ])
            ->assertCreated();

        $this->assertDatabaseCount('model_has_roles', 1);
    }
}
