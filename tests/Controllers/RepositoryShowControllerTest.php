<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

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

        $this->withoutExceptionHandling()->get('/restify-api/posts/1')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);
    }
}
