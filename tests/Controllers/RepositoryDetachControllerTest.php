<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RepositoryDetachControllerTest extends IntegrationTest
{
    public function test_can_detach_repositories(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id, [
                'is_admin' => true,
            ]);
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->assertCount(2, $company->users);

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1],
        ])->assertNoContent();

        $this->assertCount(1, $company->fresh()->users);

        $this->assertSame(
            2,
            $company->users()->get()->first()->id
        );
    }

    public function test_cant_detach_repositories_not_authorized_to_detach()
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        $this->authenticate(
            User::factory()->create()
        );

        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id, [
                'is_admin' => true,
            ]);
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $_SERVER['allow_detach_users'] = false;

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1, 2],
        ])->assertForbidden();
    }

    public function test_many_to_many_field_can_intercept_detach_authorization()
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users',  UserRepository::class)->canDetach(function ($request, $pivot) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertInstanceOf(Pivot::class, $pivot);

                    return false;
                }),
            ]);

        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1],
        ])->assertForbidden();
    }

    public function test_many_to_many_field_can_intercept_detach_method()
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users',  UserRepository::class)->detachCallback(function ($request, $repository, $model) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertInstanceOf(CompanyRepository::class, $repository);
                    $this->assertInstanceOf(Company::class, $model);

                    $model->users()->detach($request->input('users'));

                    return response()->noContent();
                }),
            ]);

        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1],
        ])->assertNoContent();

        $this->assertCount(0, $company->fresh()->users);
    }

    public function test_repository_can_intercept_detach()
    {
        $mock = CompanyRepository::partialMock();
        $mock->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users',  UserRepository::class),
            ]);

        CompanyRepository::$detachers = [
            'users' => function ($request, $repository, $model) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(CompanyRepository::class, $repository);
                $this->assertInstanceOf(Company::class, $model);

                $model->users()->detach($request->input('users'));

                return response()->noContent();
            },
        ];

        $company = tap(Company::factory()->create(), function (Company $company) {
            $company->users()->attach($this->mockUsers()->first()->id);
        });

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1],
        ])->assertNoContent();

        $this->assertCount(0, $company->fresh()->users);
    }
}
