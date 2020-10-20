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

class RepositoryDetachInterceptorTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['roles.canAttach.users'] = true;
        $_SERVER['roles.canDetach.users'] = true;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Repository::clearResolvedInstances();
    }

    public function test_can_intercept_detach_method()
    {
        $role = factory(Role::class)->create();
        $user = $this->mockUsers()->first();
        $role->users()->attach($user->id);

        $_SERVER['roles.canDetach.users'] = true;

        $this->assertCount(1, $role->users()->get());

        $this->postJson('roles/'.$role->id.'/detach/users', [
            'users' => $user->id,
        ])->assertSuccessful();

        $this->assertCount(0, $role->users()->get());

        $_SERVER['roles.canDetach.users'] = false;

        $this->postJson('roles/'.$role->id.'/detach/users', [
            'users' => $user->id,
        ])->assertForbidden();
    }

    public function test_detach_uses_field_resolver()
    {
        RoleRepository::partialMock()
            ->expects('fields')
            ->twice()
            ->andReturn([
                field('name'),
                BelongsToMany::new('users', 'users', UserRepository::class)
                    ->canDetach(function ($request, $pivot) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Pivot::class, $pivot);

                        return true;
                    })
                    ->detachCallback(function ($request, $repository, Role $model) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Repository::class, $repository);
                        $this->assertInstanceOf(Model::class, $model);

                        $model->users()->detach($request->input('users'));
                    }),
            ]);

        $role = factory(Role::class)->create();
        $user = $this->mockUsers()->first();
        $role->users()->attach($user->id);

        $this->assertCount(1, $role->users()->get());

        $this->postJson('roles/'.$role->id.'/detach/users', [
            'users' => $user->id,
        ])->assertSuccessful();

        $this->assertCount(0, $role->users()->get());
    }

    public function test_detach_uses_repository_resolver()
    {
        RoleRepository::partialMock()
                ->expects('getDetachers')
                ->twice()
                ->andReturn([
                    'users' => function (RestifyRequest $request, RoleRepository $repository, Role $model) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(RoleRepository::class, $repository);
                        $this->assertInstanceOf(Role::class, $model);

                        $model->users()->detach($request->input('users'));
                    },
                ]);

        $role = factory(Role::class)->create();
        $user = $this->mockUsers()->first();
        $role->users()->attach($user->id);

        $this->assertCount(1, $role->users()->get());

        $this->postJson('roles/'.$role->id.'/detach/users', [
            'users' => $user->id,
        ])->assertSuccessful();

        $this->assertCount(0, $role->users()->get());
    }
}
