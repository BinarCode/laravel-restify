<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class MorphToManyFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            UserWithRolesRepository::class,
        ]);
    }

    public function test_morph_to_many_displays_in_relationships()
    {
        tap(factory(User::class)->create(), function (User $user) {
            $user->roles()->attach(
                factory(Role::class, 3)->create()
            );
        });

        $this->get(UserWithRolesRepository::uriKey())
            ->assertJsonStructure([
                'data' => [[
                    'relationships' => [
                        'roles' => [],
                    ], ],
                ],
            ])->assertJsonCount(3, 'data.0.relationships.roles');
    }

    public function test_morph_to_many_ignored_when_store()
    {
        /** * @var User $user */
        $user = factory(User::class)->make();

        $id = $this->postJson(UserWithRolesRepository::uriKey(), array_merge($user->toArray(), [
            'password' => 'password',
            'users' => [1],
        ]))->json('data.id');

        $this->assertCount(0, User::find($id)->roles);
    }
}

class UserWithRolesRepository extends Repository
{
    public static $model = User::class;

    public function fields(RestifyRequest $request)
    {
        return [
            field('name'),
            field('email'),
            field('password'),

            MorphToMany::make('roles', 'roles', RoleRepository::class),
        ];
    }

    public static function uriKey()
    {
        return 'users-with-roles-repository';
    }
}
