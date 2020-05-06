<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Apple;
use Binaryk\LaravelRestify\Tests\Fixtures\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryShowControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_basic_show()
    {
        factory(Post::class)->create(['user_id' => 1]);

        $this->get('/restify-api/posts/1')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);
    }

    public function test_show_will_authorize_fields()
    {
        factory(Apple::class)->create();

        Restify::repositories([
            AppleAuthorized::class,
        ]);

        $_SERVER['can.see.title'] = false;
        $response = $this->getJson('/restify-api/apple-authorized/1');

        $this->assertArrayNotHasKey('title', $response->json('data.attributes'));

        $_SERVER['can.see.title'] = true;
        $response = $this->getJson('/restify-api/apple-authorized/1');

        $this->assertArrayHasKey('title', $response->json('data.attributes'));
    }

    public function test_show_will_take_into_consideration_show_callback()
    {
        factory(Apple::class)->create([
            'title' => 'Eduard',
        ]);

        Restify::repositories([
            AppleAuthorized::class,
        ]);

        $response = $this->getJson('/restify-api/apple-authorized/1');

        $this->assertSame('EDUARD', $response->json('data.attributes.title'));
    }

    public function test_show_unmergeable_repository_containes_only_explicitly_defined_fields()
    {
        factory(Apple::class)->create([
            'title' => 'Eduard',
        ]);

        Restify::repositories([
            AppleAuthorized::class,
        ]);

        $response = $this->getJson('/restify-api/apple-authorized/1');

        $this->assertArrayHasKey('title', $response->json('data.attributes'));

        $this->assertArrayNotHasKey('id', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('created_at', $response->json('data.attributes'));
    }

    public function test_show_mergeable_repository_containes_model_attributes_and_local_fields()
    {
        factory(Apple::class)->create([
            'title' => 'Eduard',
        ]);

        Restify::repositories([
            AppleAuthorizedMergeable::class,
        ]);

        $response = $this->getJson('/restify-api/apple-authorized-mergeable/1');

        $this->assertArrayHasKey('title', $response->json('data.attributes'));
        $this->assertArrayHasKey('id', $response->json('data.attributes'));
        $this->assertArrayHasKey('created_at', $response->json('data.attributes'));
    }
}

class AppleAuthorized extends Repository
{
    public static $uriKey = 'apple-authorized';

    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title')->canSee(fn() => $_SERVER['can.see.title'] ?? true)
            ->showCallback(fn($value) => strtoupper($value)),
        ];
    }
}

class AppleAuthorizedMergeable extends AppleAuthorized implements Mergeable
{
    public static $uriKey = 'apple-authorized-mergeable';
}
