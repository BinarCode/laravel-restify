<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class BelongsToManyFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            CompanyWithUsersRepository::class,
        ]);
    }

    public function test_belongs_to_many_displays_in_relationships()
    {
        tap(factory(Company::class)->create(), function (Company $company) {
            $company->users()->attach(
                factory(User::class, 5)->create()
            );
        });

        $this->get(CompanyWithUsersRepository::uriKey())
            ->assertJsonStructure([
                'data' => [[
                    'relationships' => [
                        'users' => [],
                    ],],
                ],
            ])->assertJsonCount(5, 'data.0.relationships.users');
    }

    public function test_belongs_to_many_ignored_when_store()
    {
        /** * @var User $user */
        $user = factory(User::class)->create();

        $companies = factory(Company::class, 5)->create();

        $user->companies()->attach($companies);

        $this->postJson(CompanyWithUsersRepository::uriKey(), [
            'name' => 'Binar Code',
            'users' => [1, 2],
        ])->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'users' => [],
                ]
            ]
        ]);
    }
}

class CompanyWithUsersRepository extends Repository
{
    public static $model = Company::class;

    public function fields(RestifyRequest $request)
    {
        return [
            field('name'),

            BelongsToMany::make('users', 'users', UserRepository::class),
        ];
    }

    public static function uriKey()
    {
        return 'companies-with-users-repository';
    }
}
