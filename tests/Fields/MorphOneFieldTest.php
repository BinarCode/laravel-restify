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
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class MorphOneFieldTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            PostWithMorphOneRepository::class,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_morph_one_present_on_show_when_specified_related(): void
    {
        $post = Post::factory()->create([
            'user_id' => User::factory(),
        ]);

        $relationships = $this
            ->withoutExceptionHandling()
            ->getJson(PostWithMorphOneRepository::route($post, query: ['related' => 'user']))
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'id',
                                'type',
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id',
                        'type',
                        'attributes' => [
                            'name',
                        ],
                    ],
                ],
            ])
            ->json('included');

        $this->assertNotNull($relationships);

        $relationships = $this->getJson(PostWithMorphOneRepository::route($post))
            ->json('included');

        $this->assertNull($relationships);
    }
}

class PostWithMorphOneRepository extends Repository
{
    public static $model = Post::class;

    public static function include(): array
    {
        return [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),

            MorphOne::make('user', UserRepository::class),
        ];
    }
}
