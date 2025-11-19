<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class RepositoryGroupByTest extends IntegrationTestCase
{
    public function test_it_can_group_by_the_results(): void
    {
        User::factory(4)->create([
            'name' => 'John Doe',
        ]);

        User::factory(4)->create([
            'name' => 'Second John Doe',
        ]);

        UserRepository::$groupBy = ['name'];

        $this->getJson(UserRepository::route(query: ['group_by' => 'name']))->assertJsonCount(2, 'data');
    }

    public function test_it_can_group_by_the_results_multiple_columns(): void
    {
        User::factory(3)->create([
            'name' => 'John Doe',
            'avatar' => 'image.jpg',
        ]);

        User::factory(1)->create([
            'name' => 'Another John Doe',
            'avatar' => 'image.jpg',
        ]);

        UserRepository::$groupBy = ['name', 'avatar'];

        $this->getJson(UserRepository::route(query: ['group_by' => 'name,avatar']))->assertJsonCount(2, 'data');
    }

    public function test_it_can_not_group_by_the_results_because_wrong_column(): void
    {
        User::factory(4)->create([
            'name' => 'John Doe',
        ]);

        User::factory(4)->create([
            'name' => 'Second John Doe',
        ]);

        UserRepository::$groupBy = ['email'];

        $this->getJson(UserRepository::route(query: ['group_by' => 'name']))->assertUnprocessable();
    }
}
