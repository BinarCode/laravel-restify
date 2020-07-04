<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\IntegrationTest;

class ListActionsControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_list_actions_for_repository()
    {
        $_SERVER['actions.posts.invalidate'] = false;

        $this->getJson('/restify-api/posts/actions')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'name',
                        'uriKey',
                    ]
                ]
            ]);
    }
}
