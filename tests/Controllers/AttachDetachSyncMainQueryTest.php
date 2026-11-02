<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class AttachDetachSyncMainQueryTest extends IntegrationTestCase
{
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Company::class, CompanyPolicy::class);
        $this->authenticate();

        $this->member = User::factory()->create();
        $_SERVER['roles.canDetach.users'] = true;
    }

    #[Test]
    public function an_attach_cannot_reach_a_parent_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hideCompany();

        $this->postJson(CompanyRepository::route("{$hidden->id}/attach/users"), ['users' => [$this->member->id], 'is_admin' => true])->assertNotFound();
        $this->postJson(CompanyRepository::route("{$visible->id}/attach/users"), ['users' => [$this->member->id], 'is_admin' => true])->assertCreated();

        $this->assertCount(0, $hidden->users()->get());
        $this->assertCount(1, $visible->users()->get());
    }

    #[Test]
    public function a_detach_cannot_reach_a_parent_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hideCompany();
        $hidden->users()->attach($this->member->id);
        $visible->users()->attach($this->member->id);

        $this->postJson(CompanyRepository::route("{$hidden->id}/detach/users"), ['users' => [$this->member->id]])->assertNotFound();
        $this->postJson(CompanyRepository::route("{$visible->id}/detach/users"), ['users' => [$this->member->id]])->assertNoContent();

        $this->assertCount(1, $hidden->users()->get());
        $this->assertCount(0, $visible->users()->get());
    }

    #[Test]
    public function a_sync_cannot_reach_a_parent_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hideCompany();

        $this->postJson(CompanyRepository::route("{$hidden->id}/sync/users"), ['users' => [$this->member->id], 'is_admin' => true])->assertNotFound();
        $this->postJson(CompanyRepository::route("{$visible->id}/sync/users"), ['users' => [$this->member->id], 'is_admin' => true])->assertOk();

        $this->assertCount(0, $hidden->users()->get());
        $this->assertCount(1, $visible->users()->get());
    }

    #[Test]
    public function the_detach_policy_receives_the_parent_model(): void
    {
        $company = Company::factory()->create();
        $company->users()->attach($this->member->id);

        $this->postJson(CompanyRepository::route("{$company->id}/detach/users"), ['users' => [$this->member->id]])->assertNoContent();

        $this->assertTrue($_SERVER['companies.detachUsers.model']->is($company));
    }

    #[Test]
    public function the_writes_opt_out_restores_the_policy_only_behaviour(): void
    {
        config()->set('restify.repositories.apply_main_query_to_writes', false);

        [$hidden] = $this->hideCompany();
        $hidden->users()->attach($this->member->id);

        $this->postJson(CompanyRepository::route("{$hidden->id}/detach/users"), ['users' => [$this->member->id]])->assertNoContent();

        $this->assertCount(0, $hidden->users()->get());
    }

    /**
     * The callback returns a clone, so a caller that ignores the builder mainQuery returns still sees both.
     *
     * @return array{Company, Company}
     */
    private function hideCompany(): array
    {
        $hidden = Company::factory()->create();
        $visible = Company::factory()->create();

        $_SERVER['restify.company.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($hidden->getKey());

        return [$hidden, $visible];
    }
}
