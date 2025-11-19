<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Models\ActionLogObserver;
use Binaryk\LaravelRestify\Tests\Assertables\AssertableActionLog;
use Binaryk\LaravelRestify\Tests\Assertables\AssertablePost;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

class ActionLogTest extends IntegrationTestCase
{
    public function test_can_create_log_for_repository_storing(): void
    {
        $this->authenticate();

        $log = ActionLog::forRepositoryStored(
            User::factory()->create(),
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

        $user = User::factory()->create([
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

        $user = User::factory()->create();

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

    public function test_can_create_log_for_repository_custom_action(): void
    {
        $this->authenticate();

        $user = User::factory()->create();

        $action = new class extends Action
        {
            public static $uriKey = 'test action';
        };

        $log = ActionLog::forRepositoryAction(
            $action,
            $user,
            $this->authenticatedAs
        );

        $log->save();

        $this->assertInstanceOf(ActionLog::class, $log);

        $this->assertDatabaseHas('action_logs', [
            'name' => 'test action',
            'actionable_type' => User::class,
            'actionable_id' => 1,
        ]);

        $this->assertDatabaseCount('action_logs', 1);
    }

    public function test_store_log_on_store_request(): void
    {
        $post = $this
            ->posts()
            ->attributes(['title' => 'Title', 'user_id' => 1])
            ->create(
                fn (AssertablePost $assertablePost) => $assertablePost
                    ->hasActionLog()
                    ->etc()
            )->model();

        $actionLog = AssertableActionLog::make($post->actionLogs()->latest()->first());

        $actionLog
            ->where('name', ActionLog::ACTION_CREATED)
            ->where('status', ActionLog::STATUS_FINISHED)
            ->where('actionable_type', get_class($post))
            ->where('actionable_id', $post->getKey())
            ->where('original', '')
            ->where('changes.user_id', 1)
            ->where('changes.title', 'Title')
            ->etc();
    }

    public function test_store_log_on_update_request(): void
    {
        $post = $this
            ->posts()
            ->attributes(['title' => 'Title'])
            ->create()
            ->attributes(['title' => 'Updated post'])
            ->update(
                assertable: fn (AssertablePost $assertablePost) => $assertablePost
                    ->hasActionLog(2)
                    ->etc()
            )->model();

        $actionLog = AssertableActionLog::make($post->actionLogs()->latest('id')->first());

        $actionLog
            ->where('name', ActionLog::ACTION_UPDATED)
            ->where('status', ActionLog::STATUS_FINISHED)
            ->where('actionable_type', get_class($post))
            ->where('actionable_id', $post->getKey())
            ->where('original', ['title' => 'Title'])
            ->where('changes', ['title' => 'Updated post'])
            ->where('user_id', Auth::id())
            ->etc();
    }

    public function test_store_log_on_destroy_request(): void
    {
        $_SERVER['restify.post.delete'] = true;

        $post = PostFactory::one(['title' => 'Title']);

        $this->assertEmpty($post->actionLogs()->get());

        Post::observe(ActionLogObserver::class);

        $this
            ->posts()
            ->attributes(['title' => 'Updated post'])
            ->destroy(
                key: $post->getKey(),
                tap: fn (TestResponse $assertablePost) => $assertablePost
                    ->assertNoContent()
            );

        $actionLog = AssertableActionLog::make($post->actionLogs()->latest()->first());

        $actionLog
            ->where('name', ActionLog::ACTION_DELETED)
            ->where('status', ActionLog::STATUS_FINISHED)
            ->where('actionable_type', get_class($post))
            ->where('actionable_id', $post->getKey())
            ->where('original.id', $post->getKey())
            ->where('original.title', $post->title)
            ->where('changes', null)
            ->where('user_id', Auth::id())
            ->etc();
    }

    public function test_store_log_when_creating_outside_restify(): void
    {
        config()->set('restify.logs.all', true);

        $post = PostFactory::one(['title' => 'Title']);

        $this->assertCount(1, $post->actionLogs()->get());

        $actionLog = AssertableActionLog::make($post->actionLogs()->latest()->first());

        $actionLog
            ->where('name', ActionLog::ACTION_CREATED)
            ->where('status', ActionLog::STATUS_FINISHED)
            ->where('actionable_type', get_class($post))
            ->where('actionable_id', $post->getKey())
            ->where('original', '')
            ->where('changes.title', 'Title')
            ->where('user_id', null)
            ->etc();

        config()->set('restify.logs.all', false);
    }

    public function test_store_log_on_action_request(): void
    {
        $_SERVER['actions.posts.publish.onlyOnShow'] = false;

        Post::observe(ActionLogObserver::class);

        $post = $this
            ->posts()
            ->attributes(['title' => 'Title', 'user_id' => 1, 'is_active' => false])
            ->create()
            ->model();

        $this
            ->posts()
            ->runAction(PublishPostAction::class, [
                'repositories' => [1],
            ]);

        $this->assertTrue($post->fresh()->is_active);

        $actionLog = AssertableActionLog::make($post->actionLogs()->latest('id')->first());

        $actionLog
            ->where('name', PublishPostAction::$uriKey)
            ->where('status', ActionLog::STATUS_FINISHED)
            ->where('actionable_type', get_class($post))
            ->where('actionable_id', $post->getKey())
            ->where('original.is_active', false)
            ->where('changes.is_active', true)
            ->where('user_id', Auth::id())
            ->etc();
    }

    public function test_can_store_custom_logs(): void
    {
        $post = PostFactory::one();

        $log = ActionLog::register('Activated post', $post, [])
            ->actor($this->authenticatedAs)
            ->target($post)
            ->withMeta('foo', 'bar');

        $log->save();

        AssertableActionLog::make($log->fresh())
            ->where('target_type', $post->getMorphClass())
            ->where('meta.foo', 'bar');

        $this->assertDatabaseHas('action_logs', [
            'name' => 'Activated post',
            'actionable_type' => $post::class,
            'actionable_id' => $post->getKey(),
            'user_id' => $this->authenticatedAs->getKey(),
        ]);
    }

    public function test_store_all_logs_when_enabled_and_go_through_restify_and_mutate_from_side_effect(): void
    {
        $post = $this
            ->posts()
            ->attributes(['title' => 'Title'])
            ->create()
            ->attributes(['title' => 'Updated post'])
            ->update(
                assertable: fn (AssertablePost $assertablePost) => $assertablePost
                    ->hasActionLog(2)
                    ->etc()
            )
            ->model();

        $post->update(['title' => 'A title set outside of restify.']);

        $this->assertCount(3, $post->actionLogs()->get());
    }
}
