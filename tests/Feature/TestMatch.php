<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TestMatch extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Repository::clearResolvedInstances();
    }

    public function test_repository_filter_works()
    {
        PostRepository::$match = [
            'title' => RestifySearchable::MATCH_TEXT,
        ];

        $this->posts()
            ->getJson('posts?title=Another one')
            ->assertJsonCount(1, 'data')
            ->assertOk();
    }

    public function test_closure_works()
    {
        PostRepository::$match['active'] = function ($request, $query) {
            $this->assertInstanceOf(Request::class, $request);
            $this->assertInstanceOf(Builder::class, $query);

            $query->where('is_active', $request->boolean('active'));
        };

        $this->posts()
            ->getJson('posts?active=true')
            ->assertJsonCount(2, 'data')
            ->assertOk();

        $this->getJson('posts?active=false')
            ->assertJsonCount(1, 'data')
            ->assertOk();
    }

    public function test_closure_matchable()
    {
        PostRepository::$match['active'] = ActiveMatch::class;

        $this->posts()
            ->getJson('posts?active=true')
            ->assertJsonCount(2, 'data')
            ->assertOk();

        $this->getJson('posts?active=false')
            ->assertJsonCount(1, 'data')
            ->assertOk();
    }

    private function posts()
    {
        Post::factory()->create([
            'is_active' => true,
            'title' => 'Post 1',
        ]);

        Post::factory()->create([
            'is_active' => true,
            'title' => 'Post 2',
        ]);

        Post::factory()->create([
            'is_active' => false,
            'title' => 'Another one',
        ]);

        return $this;
    }
}
