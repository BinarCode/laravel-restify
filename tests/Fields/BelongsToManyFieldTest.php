<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class BelongsToManyFieldTest extends IntegrationTest
{
    public function test_belongs_to_many_displays_on_relationships_show()
    {
        $company = tap(factory(Company::class)->create(), function (Company $company) {
            $company->users()->attach(
                factory(User::class, 5)->create()
            );
        });

        $this->get(CompanyRepository::uriKey()."/{$company->id}?related=users")
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'users' => [],
                    ],
                ],
            ])->assertJsonCount(5, 'data.relationships.users');
    }

    public function test_belongs_to_many_can_hide_relationships_from_show()
    {
        $company = tap(factory(Company::class)->create(), function (Company $company) {
            $company->users()->attach(
                factory(User::class, 5)->create()
            );
        });

        CompanyRepository::partialMock()
            ->expects('related')
            ->andReturn([
                'users' => BelongsToMany::make('users', 'users', UserRepository::class)->hideFromShow(),
            ]);

        $this->get(CompanyRepository::uriKey()."/{$company->id}?related=users")
            ->assertJsonStructure([
                'data' => [],
            ])->assertJsonMissing([
                'users',
            ]);
    }

    public function test_belongs_to_many_can_hide_relationships_from_index()
    {
        tap(factory(Company::class)->create(), function (Company $company) {
            $company->users()->attach(
                factory(User::class)->create()
            );
        });

        CompanyRepository::partialMock()
            ->expects('related')
            ->andReturn([
                'users' => BelongsToMany::make('users', 'users', UserRepository::class)->hideFromIndex(),
            ]);

        $this->get(CompanyRepository::uriKey().'?related=users')->assertJsonMissing([
            'users',
        ]);

        CompanyRepository::partialMock()
            ->expects('related')
            ->andReturn([
                'users' => BelongsToMany::make('users', 'users', UserRepository::class)->hideFromShow(),
            ]);

        $this->get(CompanyRepository::uriKey().'?related=users')->assertJsonFragment([
            'users',
        ]);
    }

    public function test_belongs_to_many_generates_nested_uri()
    {
        $company = tap(factory(Company::class)->create(), function (Company $company) {
            $company->users()->attach(
                factory(User::class)->create()
            );
        });

        $response = $this->get(CompanyRepository::uriKey()."/{$company->id}/users")
            ->dump()
            ->assertOk();

        $this->assertSame(
            'users',
            $response->json('data.0.type')
        );
    }

    public function test_belongs_to_many_ignored_when_storing()
    {
        /** * @var User $user */
        $user = factory(User::class)->create();

        $companies = factory(Company::class, 5)->create();

        $user->companies()->attach($companies);

        $this->postJson(CompanyRepository::uriKey(), [
            'name' => 'Binar Code',
            'users' => [1, 2],
        ])->assertJsonMissing([
            [
                'relationships' => [
                    'users' => [],
                ], ],
        ]);
    }
}
