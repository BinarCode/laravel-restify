<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\VerifiedMatcher;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class RepositorySearchServiceTest extends IntegrationTest
{
    public function test_can_search_using_filter_searchable_definition(): void
    {
        User::factory(4)->create([
            'name' => 'John Doe',
        ]);

        User::factory(4)->create([
            'name' => 'wew',
        ]);

        UserRepository::$search = [
            'name' => CustomSearchableFilter::make(),
        ];

        $this->getJson('users?search=John')->assertJsonCount(4, 'data');
    }

    public function test_can_search_incase_sensitive(): void
    {
        config()->set('restify.search.case_sensitive', false);

        User::factory(4)->create([
            'name' => 'JOHN DOE',
        ]);

        User::factory(4)->create([
            'name' => 'wew',
        ]);

        UserRepository::$search = [
            'name',
        ];

        $this->getJson('users?search=John')->assertJsonCount(4, 'data');
    }

    public function test_can_search_using_belongs_to_field(): void
    {
        $foreignUser = User::factory()->create([
            'name' => 'Curtis Dog',
        ]);

        Post::factory(4)->create([
            'user_id' => $foreignUser->id,
        ]);

        $john = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Post::factory(2)->create([
            'user_id' => $john->id,
        ]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user',  UserRepository::class)->searchable([
                'users.name',
            ]),
        ];

        $this->getJson('posts?search=John')
            ->assertJsonCount(2, 'data');
    }

    public function test_can_match_custom_matcher(): void
    {
        User::factory(1)->create([
            'email_verified_at' => now(),
        ]);

        User::factory(2)->create([
            'email_verified_at' => null,
        ]);

        UserRepository::$match = ['verified' => VerifiedMatcher::make()];
        $this->getJson('users?verified=true')->assertJsonCount(1, 'data');

        UserRepository::$match = ['verified' => VerifiedMatcher::make()];
        $this->getJson('users?verified=false')->assertJsonCount(2, 'data');
    }
}

class CustomSearchableFilter extends SearchableFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        return $query->orWhere('name', 'like', "%$value%");
    }
}
