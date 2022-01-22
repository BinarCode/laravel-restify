<?php

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class ListGettersControllerTest extends IntegrationTest
{
    public function test_could_list_getters_for_repository(): void
    {
        $this->getJson(PostRepository::to('getters'))
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data')
                ->where('data.0.uriKey', 'posts-index-getter')
                ->count('data', 2)
                ->etc()
            );
    }

    public function test_could_list_getters_for_given_repository(): void
    {
        $this->mockPosts(1, 2);

        $this->getJson(PostRepository::to('1/getters'))
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data')
                ->where('data.1.uriKey', 'posts-show-getter')
                ->count('data', 3)
                ->etc()
            );
    }
}
