<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class ActionLogTest extends IntegrationTest
{
    public function test_can_create_log_for_repository_storing()
    {
        $this->authenticate();

        $log = ActionLog::forRepositoryStored(
            factory(User::class)->create(),
            $this->authenticatedAs
        );

        $log->save();

        $this->assertInstanceOf(ActionLog::class, $log);

        $this->assertDatabaseHas('action_logs', [
            'name' => 'Stored',
            'actionable_type' => User::class,
            'actionable_id' => 1,
        ]);

        $this->assertDatabaseCount('action_logs', 1);
    }

    public function test_can_create_log_for_repository_updating()
    {
        $this->authenticate();

        $user = factory(User::class)->create([
            'email' => 'initial@mail',
        ]);

        $user->email = 'foo@bar.com';

        $log = ActionLog::forRepositoryUpdated(
            $user,
            $this->authenticatedAs
        );

        $log->save();

        $this->assertInstanceOf(ActionLog::class, $log);

        $this->assertDatabaseHas('action_logs', [
            'name' => ActionLog::ACTION_UPDATED,
            'actionable_type' => User::class,
            'actionable_id' => 1,
        ]);

        $this->assertDatabaseCount('action_logs', 1);

        $this->assertSame([
            'email' => 'foo@bar.com',
        ], $log->changes);

        $this->assertSame([
            'email' => 'initial@mail',
        ], $log->original);
    }

    public function test_can_create_log_for_repository_deleting()
    {
        $this->authenticate();

        $user = factory(User::class)->create();

        $log = ActionLog::forRepositoryDestroy(
            $user,
            $this->authenticatedAs
        );

        $log->save();

        $this->assertInstanceOf(ActionLog::class, $log);

        $this->assertDatabaseHas('action_logs', [
            'name' => ActionLog::ACTION_DELETED,
            'actionable_type' => User::class,
            'actionable_id' => 1,
        ]);

        $this->assertDatabaseCount('action_logs', 1);
    }
}
