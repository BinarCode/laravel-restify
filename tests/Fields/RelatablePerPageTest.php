<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatablePerPageTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();

        Restify::repositories([
            UserWithPostsForRelatablePerPage::class,
        ]);
    }

    protected function tearDown(): void
    {
        Repository::clearResolvedInstances();

        parent::tearDown();
    }

    #[Test]
    public function it_clamps_relatable_per_page_on_a_has_many_field(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        $user = tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 55);
        });

        $this->getJson(UserWithPostsForRelatablePerPage::route($user, query: [
            'related' => 'posts',
            'relatablePerPage' => 100,
        ]))->assertJsonCount(50, 'data.relationships.posts');
    }

    #[Test]
    #[TestWith(['abc'], 'non-numeric relatablePerPage falls back to the default')]
    #[TestWith([-1], 'negative relatablePerPage falls back to the default')]
    public function it_falls_back_to_the_default_for_an_invalid_relatable_per_page_on_a_has_many_field(int|string $relatablePerPage): void
    {
        $user = tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 20);
        });

        $this->getJson(UserWithPostsForRelatablePerPage::route($user, query: [
            'related' => 'posts',
            'relatablePerPage' => $relatablePerPage,
        ]))->assertJsonCount(15, 'data.relationships.posts');
    }

    #[Test]
    public function it_resolves_relatable_per_page_merged_into_the_request_directly(): void
    {
        // HasMany/BelongsToMany read relatablePerPage via the request('relatablePerPage')
        // helper (not a resolved RestifyRequest), so a consumer setting it with
        // app('request')->merge(...) - rather than the query string - must still work.
        config(['restify.pagination.max_per_page' => 50]);

        app('request')->merge(['relatablePerPage' => 100]);

        $this->assertSame(
            50,
            PaginationDataObject::fromInput(request('relatablePerPage'), null)->resolvePerPage(15)
        );
    }

    #[Test]
    public function it_clamps_relatable_per_page_on_a_belongs_to_many_field(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        $company = $this->companyWithAttachedUsers(55);

        $this->getJson(CompanyRepository::route($company, query: [
            'related' => 'users',
            'relatablePerPage' => 100,
        ]))->assertJsonCount(50, 'data.relationships.users');
    }

    #[Test]
    #[TestWith(['abc'], 'non-numeric relatablePerPage falls back to the default')]
    #[TestWith([-1], 'negative relatablePerPage falls back to the default')]
    public function it_falls_back_to_the_default_for_an_invalid_relatable_per_page_on_a_belongs_to_many_field(int|string $relatablePerPage): void
    {
        $company = $this->companyWithAttachedUsers(20);

        $this->getJson(CompanyRepository::route($company, query: [
            'related' => 'users',
            'relatablePerPage' => $relatablePerPage,
        ]))->assertJsonCount(15, 'data.relationships.users');
    }

    private function companyWithAttachedUsers(int $count): Company
    {
        $company = Company::factory()->create();

        $company->users()->attach(
            User::factory()->count($count)->create()->pluck('id')
        );

        return $company;
    }
}

class UserWithPostsForRelatablePerPage extends Repository
{
    public static $model = User::class;

    public static string $uriKey = 'relatable-per-page-users';

    public static function include(): array
    {
        return [
            HasMany::make('posts', PostRepository::class),
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
}
