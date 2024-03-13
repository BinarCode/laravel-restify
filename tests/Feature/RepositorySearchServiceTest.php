<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\VerifiedMatcher;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RepositorySearchServiceTest extends IntegrationTestCase
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

        $this->getJson(UserRepository::route(query: ['search' => 'John']))->assertJsonCount(4, 'data');
    }

    public function test_can_search_case_insensitive(): void
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

        $this->getJson(UserRepository::route(query: ['search' => 'John']))->assertJsonCount(4, 'data');
    }

    public function test_search_correctly_using_quotes(): void
    {
        config()->set('restify.search.case_sensitive', false);

        User::factory(4)->create([
            'name' => "Brian O'Donnel",
        ]);

        User::factory(4)->create([
            'name' => 'wew',
        ]);

        UserRepository::$search = [
            'name',
        ];

        $this->getJson(UserRepository::route(query: ['search' => "O'Donnel"]))
            ->assertJsonCount(4, 'data');
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
            BelongsTo::make('user', UserRepository::class)->searchable('name'),
        ];

        $this->getJson(PostRepository::route(query: ['search' => 'John']))
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_using_belongs_to_field_with_custom_foreign_key(): void
    {
        $foreignUser = User::factory()->create([
            'name' => 'Curtis Dog',
        ]);

        Post::factory(4)->create([
            'edited_by' => $foreignUser->id,
        ]);

        $john = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Post::factory(2)->create([
            'edited_by' => $john->id,
        ]);

        PostRepository::$related = [
            'editor' => BelongsTo::make('editor', UserRepository::class)->searchable([
                'users.name',
            ]),
        ];

        $this->withoutExceptionHandling();
        $this->getJson(PostRepository::route(query: ['search' => 'John']))
            ->assertJsonCount(2, 'data');
    }

    public function test_can_match_closure(): void
    {
        User::factory(4)->create();

        UserRepository::$match = [
            'is_active' => function ($request, $query) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Builder::class, $query);
            },
        ];

        $this->getJson('users?is_active=true')
            ->assertStatus(404);
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
        $this->getJson(UserRepository::route(query: ['verified' => 'true']))->assertJsonCount(1, 'data');

        UserRepository::$match = ['verified' => VerifiedMatcher::make()];
        $this->getJson(UserRepository::route(query: ['verified' => 'false']))->assertJsonCount(2, 'data');
    }
}

class CustomSearchableFilter extends SearchableFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        return $query->orWhere('name', 'like', "%$value%");
    }
}
