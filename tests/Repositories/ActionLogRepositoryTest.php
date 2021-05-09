<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Repositories\ActionLogRepository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class ActionLogRepositoryTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            ActionLogRepository::class,
        ]);
    }

    public function test_can_list_action_logs()
    {
        $this->authenticate();

        $log = ActionLog::forRepositoryStored(
            User::factory()->create(),
            $this->authenticatedAs
        );

        $log->save();

        $this->getJson(ActionLogRepository::uriKey())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'type',
                        'attributes' => [
                            'name',
                            'user_id',
                            'actionable_type',
                            'actionable_id',
                        ],
                    ],
                ],
            ])->json('data');
    }
}
