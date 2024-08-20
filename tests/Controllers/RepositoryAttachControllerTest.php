<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

use function PHPUnit\Framework\assertInstanceOf;

class RepositoryAttachControllerTest extends IntegrationTestCase
{
    public function test_can_attach_repositories(): void
    {
        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        $this->assertCount(0, Company::first()->users);

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
            'is_admin' => true,
        ])
            ->assertCreated()->assertJsonFragment([
                'company_id' => '1',
                'user_id' => $user->getKey(),
                'is_admin' => true,
            ]);

        $this->assertCount(1, Company::first()->users);
    }

    public function test_cant_attach_repositories_not_authorized_to_attach(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        $this->authenticate(
            User::factory()->create()
        );

        $_SERVER['allow_attach_users'] = false;

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
            'is_admin' => true,
        ])->assertForbidden();

        $_SERVER['allow_attach_users'] = true;

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
            'is_admin' => true,
        ])->assertCreated();

        unset($_SERVER['allow_attach_users']);
    }

    public function test_attach_pivot_field_validation(): void
    {
        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class)->withPivot(
                    Field::make('is_admin')->rules('required')->messages([
                        'required' => $message = 'You should fill the is_admin information.',
                    ])
                ),
            ]);

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
        ])->assertStatus(422)->assertJsonFragment([
            'is_admin' => [
                $message,
            ],
        ]);
    }

    public function test_pivot_field_present_when_show(): void
    {
        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id, [
                'is_admin' => true,
            ]);
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->getJson(CompanyRepository::route($company->id, query: [
            'include' => 'users',
        ]))->assertOk()->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.relationships.users.0.pivots.is_admin', true)
                ->where('data.relationships.users.1.pivots.is_admin', false)
                ->etc()
        );
    }

    public function test_pivot_field_present_when_index(): void
    {
        tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id, [
                'is_admin' => true,
            ]);
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->getJson(CompanyRepository::route(query: [
            'include' => 'users',
        ]))->assertOk()->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.relationships.users.0.pivots.is_admin', true)
                ->where('data.0.relationships.users.1.pivots.is_admin', false)
                ->etc()
        );
    }

    public function test_attach_multiple_users_to_a_company(): void
    {
        $user = $this->mockUsers(2)->first();
        $company = Company::factory()->create();

        $this->assertCount(0, $company->users);

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => [1, 2],
            'is_admin' => true,
        ])->assertCreated()->assertJsonFragment([
            'company_id' => '1',
            'user_id' => $user->getKey(),
            'is_admin' => true,
        ]);

        $this->assertCount(2, $company->fresh()->users);
    }

    public function test_many_to_many_field_can_intercept_attach_authorization(): void
    {
        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class)
                    ->canAttach(function ($request, $pivot) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertInstanceOf(Pivot::class, $pivot);

                        return false;
                    }),
            ]);

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
            'is_admin' => true,
        ])->assertForbidden();
    }

    public function test_many_to_many_field_can_intercept_attach_method(): void
    {
        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class)
                    ->canAttach(function ($request, $pivot) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertInstanceOf(Pivot::class, $pivot);

                        return true;
                    })
                    ->attachCallback(new AttachInvokable),
            ]);

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
            'is_admin' => true,
        ])->assertOk();

        $this->assertCount(1, Company::first()->users);
    }

    public function test_repository_can_intercept_attach(): void
    {
        $user = $this->mockUsers()->first();
        $company = Company::factory()->create();

        CompanyRepository::partialMock()->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class),
            ]);

        CompanyRepository::$attachers = [
            'users' => function ($request, $repository, $model) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(CompanyRepository::class, $repository);
                $this->assertInstanceOf(Company::class, $model);

                $model->users()->attach($request->input('users'));
            },
        ];

        $this->postJson(CompanyRepository::route("$company->id/attach/users"), [
            'users' => $user->getKey(),
        ])->assertOk();

        $this->assertCount(1, $company->fresh()->users);
    }
}

class AttachInvokable
{
    public function __invoke($request, $repository, $model): void
    {
        assertInstanceOf(Request::class, $request);
        assertInstanceOf(CompanyRepository::class, $repository);
        assertInstanceOf(Company::class, $model);

        $model->users()->attach($request->input('users'));
    }
}
