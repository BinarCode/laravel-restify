<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\MorphOne;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class MorphOneFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            PostWithMophOneRepository::class,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_morph_one_present_on_show_when_specified_related()
    {
        $post = factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]);

        $relationships = $this->get(PostWithMophOneRepository::uriKey()."/$post->id?related=user")
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'user' => [
                            'id',
                            'type',
                            'attributes',
                        ],
                    ],
                ],
            ])
            ->json('data.relationships');

        $this->assertNotNull($relationships);

        $relationships = $this->get(PostWithMophOneRepository::uriKey()."/$post->id")
            ->json('data.relationships');

        $this->assertNull($relationships);
    }
}

class PostWithMophOneRepository extends Repository
{
    public static $model = Post::class;

    public static function getRelated()
    {
        return [
            'user' => BelongsTo::make('user', 'user', UserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request)
    {
        return [
            field('title'),

            MorphOne::make('user', 'user', UserRepository::class),
        ];
    }
}
