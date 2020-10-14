<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class HasOneFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Restify::repositories([
            UserWithPostRepository::class,
        ]);
    }

    public function test_has_one_will_be_returned_in_relations()
    {
        $user = factory(User::class)->create();

        factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        $this->getJson('/restify-api/' . UserWithPostRepository::uriKey())->assertJsonStructure([
            'data' => [
                [
                    'relationships' => [
                        'post',
                    ],
                ],
            ],
        ]);
    }
}

class UserWithPostRepository extends Repository
{
    public static $model = User::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('name'),
            Field::new('email'),
            Field::new('password'),

            HasOne::make('post', 'post', PostRepository::class),
        ];
    }

    public static function uriKey()
    {
        return 'user-with-post-repository';
    }
}

class PostRepository extends Repository
{
    public static $model = Post::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),

            Field::new('description')->storingRules('required'),
        ];
    }
}
