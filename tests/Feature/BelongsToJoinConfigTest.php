<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

/**
 * Test cases for the configurable JOIN optimization feature for BelongsTo relationship searches.
 * 
 * This test ensures that both JOIN-based (optimized) and subquery-based (legacy) approaches
 * work correctly based on the configuration setting.
 */
class BelongsToJoinConfigTest extends IntegrationTestCase
{
    public function test_belongs_to_search_works_with_joins_disabled(): void
    {
        // Disable JOINs
        config(['restify.search.use_joins_for_belongs_to' => false]);
        
        $john = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Post::factory(2)->create([
            'edited_by' => $john->id,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Other User',
        ]);

        Post::factory(1)->create([
            'edited_by' => $otherUser->id,
        ]);

        PostRepository::$related = [
            'editor' => BelongsTo::make('editor', UserRepository::class)->searchable([
                'users.name',
            ]),
        ];

        $this->getJson(PostRepository::route(query: ['search' => 'John']))
            ->assertJsonCount(2, 'data');
    }
    
    public function test_belongs_to_search_works_with_joins_enabled(): void
    {
        // Enable JOINs (default)
        config(['restify.search.use_joins_for_belongs_to' => true]);
        
        $john = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Post::factory(2)->create([
            'edited_by' => $john->id,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Other User',
        ]);

        Post::factory(1)->create([
            'edited_by' => $otherUser->id,
        ]);

        PostRepository::$related = [
            'editor' => BelongsTo::make('editor', UserRepository::class)->searchable([
                'users.name',
            ]),
        ];

        $this->getJson(PostRepository::route(query: ['search' => 'John']))
            ->assertJsonCount(2, 'data');
    }
    
    protected function tearDown(): void
    {
        // Reset to default
        config(['restify.search.use_joins_for_belongs_to' => true]);
        
        parent::tearDown();
    }
}