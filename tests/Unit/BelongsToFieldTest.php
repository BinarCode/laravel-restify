<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class BelongsToFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Restify::repositories([
            PostWithUserRepository::class,
        ]);
    }

    public function test_field_will_be_returned_in_relations()
    {
        factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]);

        $this->getJson('/restify-api/' . PostWithUserRepository::uriKey())
            ->assertJsonStructure([
                'data' => [
                    [
                        'attributes' => [
                            'user' => [
                                'attributes',
                            ],
                        ],
                    ]
                ]
            ]);
    }

    public function test_belongs_to_field_is_ignored_when_storing()
    {
        factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]);

        $this->postJson('/restify-api/' . PostWithUserRepository::uriKey(), [
            'title' => 'New Post with user',
            'user' => [
                'name' => 'Eduard Lupacescu',
                'email' => 'eduard.lupacescu@binarcode.com',
            ],
        ])
            ->assertCreated();
    }
}

class PostWithUserRepository extends Repository
{
    public static $model = Post::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title'),

            BelongsTo::make('user', 'user', UserRepository::class),
        ];
    }

    public static function uriKey()
    {
        return 'posts-with-user-repository';
    }
}
