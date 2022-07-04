<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class MorphToManyFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            UserWithRolesRepository::class,
        ]);
    }

    public function test_morph_to_many_displays_in_relationships(): void
    {
        $user = tap(User::factory()->create(), function (User $user) {
            $user->roles()->attach(
                Role::factory(3)->create()
            );
        });

        $this->getJson(UserWithRolesRepository::route($user->getKey(), [
            'related' => 'roles',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
            ->count('data.relationships.roles', 3)
            ->etc()
        );
    }

    public function test_morph_to_many_works_with_belongs_to_many(): void
    {
        /** * @var User $user */
        $user = User::factory()->create();

        tap(Company::factory()->create(), function (Company $company) use ($user) {
            $company->users()->attach($user->getKey());

            $user->roles()->attach(
                Role::factory(3)->create()
            );
        });

        $this->getJson(UserWithRolesRepository::uriKey()."/$user->id?related=roles,companies")
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'roles' => [],
                        'companies' => [],
                    ],
                ],
            ])->assertJsonCount(3, 'data.relationships.roles');
    }

    public function test_morph_to_many_ignored_when_store()
    {
        /** * @var User $user */
        $user = User::factory()->make();

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

    public static function include(): array
    {
        return [
            'roles' => MorphToMany::make('roles', RoleRepository::class),
            'companies' => BelongsToMany::make('companies', CompanyRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
            field('password'),
        ];
    }

    public static function uriKey(): string
    {
        return 'users-with-roles-repository';
    }
}
