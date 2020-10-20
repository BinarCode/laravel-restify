<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RepositoryAttachInterceptorTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Repository::clearResolvedInstances();
    }

    public function test_can_intercept_attach_method()
    {
        $role = factory(Role::class)->create();
        $user = $this->mockUsers()->first();

        $_SERVER['roles.canAttach.users'] = true;

        $this->postJson('roles/' . $role->id . '/attach/users', [
            'users' => $user->id,
        ])->assertCreated();

        $this->assertDatabaseCount('model_has_roles', 1);

        $_SERVER['roles.canAttach.users'] = false;

        $this->postJson('roles/' . $role->id . '/attach/users', [
            'users' => $user->id,
        ])->assertForbidden();
    }

    public function test_attach_uses_field_resolver()
    {
        $this->mock(BelongsToMany::class);

        RoleRepository::partialMock()
            ->expects('fields')
            ->twice()
            ->andReturn([
                field('name'),
                BelongsToMany::new('users', 'users', UserRepository::class)
                    ->canAttach(function ($request, $pivot) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Pivot::class, $pivot);
                        return true;
                    })
                    ->attachCallback(function ($request, $repository, Role $model) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Repository::class, $repository);
                        $this->assertInstanceOf(Model::class, $model);

                        $model->users()->attach($request->input('users'));
                    })
            ]);

        $role = factory(Role::class)->create();

        $this->assertCount(0, $role->users()->get());

        $user = $this->mockUsers()->first();

        $this->postJson('roles/' . $role->id . '/attach/users', [
            'users' => $user->id,
        ])->assertSuccessful();

        $this->assertCount(1, $role->users()->get());
    }
}
