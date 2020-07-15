<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Route;

class RepositorySearchServiceTest extends IntegrationTest
{
    /** * @var RepositorySearchService $service*/
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_match_date()
    {
        factory(User::class)->create([
            'created_at' => null,
        ]);

        factory(User::class)->create([
            'created_at' => '01-12-2020'
        ]);

        UserRepository::$match = [
            'created_at' => RestifySearchable::MATCH_DATETIME,
        ];


        $this->getJson('restify-api/users?created_at=null')
            ->assertJsonCount(1, 'data');

        $this->getJson('restify-api/users?created_at=2020-12-01')
            ->assertJsonCount(1, 'data');
    }

    public function test_can_match_array()
    {
        factory(User::class, 4)->create();

        UserRepository::$match = [
            'id' => RestifySearchable::MATCH_ARRAY,
            '-id' => RestifySearchable::MATCH_ARRAY,
        ];


        $this->getJson('restify-api/users?id=1,2,3')
            ->assertJsonCount(3, 'data');

        $this->getJson('restify-api/users?-id=1,2,3')
            ->assertJsonCount(1, 'data');
    }
}
