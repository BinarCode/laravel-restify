<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Fields\MorphMany;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\Test;

class RelatableRoutesAuthorizationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([PostActionLogRepository::class, SinglePostRepository::class, UserByEmailRepository::class]);

        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
            'post' => HasOne::make('post', SinglePostRepository::class),
            'companies' => BelongsToMany::make('companies', CompanyRepository::class),
            'roles' => MorphToMany::make('roles', RoleRepository::class),
        ];

        PostRepository::$related = [
            'actionLogs' => MorphMany::make('actionLogs', PostActionLogRepository::class),
        ];
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
    }

    #[Test]
    public function a_parent_hidden_by_main_query_answers_404_on_every_relatable_route(): void
    {
        [$hidden, $visible] = $this->hideUser();
        $hiddenPost = Post::factory()->create(['user_id' => $hidden->id, 'title' => 'Hidden']);
        $visiblePost = Post::factory()->create(['user_id' => $visible->id, 'title' => 'Visible']);

        $this->getJson(UserRepository::route("{$hidden->id}/posts"))->assertNotFound();
        $this->getJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"))->assertNotFound();
        $this->postJson(UserRepository::route("{$hidden->id}/posts"), ['title' => 'Created'])->assertNotFound();
        $this->postJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"), ['title' => 'Changed'])->assertNotFound();
        $this->putJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"), ['title' => 'Changed'])->assertNotFound();
        $this->deleteJson(UserRepository::route("{$hidden->id}/posts/{$hiddenPost->id}"))->assertNotFound();

        $this->assertSame(['Hidden'], $hidden->posts()->pluck('title')->all());

        $this->getJson(UserRepository::route("{$visible->id}/posts"))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"))->assertOk();
        $this->postJson(UserRepository::route("{$visible->id}/posts"), ['title' => 'Created'])->assertCreated();
        $this->putJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"), ['title' => 'Changed'])->assertOk();
        $this->deleteJson(UserRepository::route("{$visible->id}/posts/{$visiblePost->id}"))->assertNoContent();

        $this->assertSame(['Created'], $visible->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_missing_parent_answers_404(): void
    {
        $this->getJson(UserRepository::route('999/posts'))->assertNotFound();
        $this->postJson(UserRepository::route('999/posts'), ['title' => 'Created'])->assertNotFound();

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function a_parent_the_show_policy_denies_answers_403_on_every_relatable_route(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Kept']);
        $_SERVER['restify.users.show'] = false;

        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertForbidden();
        $this->getJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();
        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created'])->assertForbidden();
        $this->putJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->deleteJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();

        $this->assertSame(['Kept'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function writing_a_child_requires_the_parent_update_policy(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Kept']);
        $_SERVER['restify.users.update'] = false;

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created'])->assertForbidden();
        $this->postJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->putJson(UserRepository::route("{$user->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertForbidden();
        $this->deleteJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertForbidden();

        $this->assertSame(['Kept'], $user->posts()->pluck('title')->all());

        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(UserRepository::route("{$user->id}/posts/{$post->id}"))->assertOk();
    }

    #[Test]
    public function the_parent_is_checked_before_the_child_is_validated(): void
    {
        [$hidden] = $this->hideUser();
        $_SERVER['restify.users.show'] = false;

        $this->postJson(UserRepository::route("{$hidden->id}/posts"))->assertNotFound();

        $_SERVER['restify.user.mainQueryCallback'] = null;

        $this->postJson(UserRepository::route("{$hidden->id}/posts"))->assertForbidden();
    }

    #[Test]
    public function the_writes_opt_out_skips_the_parent_main_query_but_keeps_its_policy(): void
    {
        config()->set('restify.repositories.apply_main_query_to_writes', false);

        [$hidden] = $this->hideUser();

        $this->postJson(UserRepository::route("{$hidden->id}/posts"), ['title' => 'Created'])->assertCreated();
        $this->assertSame(['Created'], $hidden->posts()->pluck('title')->all());

        $this->getJson(UserRepository::route("{$hidden->id}/posts"))->assertNotFound();

        $_SERVER['restify.users.show'] = false;

        $this->postJson(UserRepository::route("{$hidden->id}/posts"), ['title' => 'Denied'])->assertForbidden();
    }

    #[Test]
    public function the_parent_is_found_by_its_route_key(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        Post::factory()->create(['user_id' => $user->id, 'title' => 'Owned']);

        $this->getJson(UserByEmailRepository::route('owner@example.com/posts'))
            ->assertOk()
            ->assertJsonPath('data.0.attributes.title', 'Owned');

        $this->getJson(UserByEmailRepository::route("{$user->id}/posts"))->assertNotFound();
    }

    #[Test]
    public function a_cached_index_still_checks_the_parent(): void
    {
        $this->enableRepositoryCache();
        config(['cache.default' => 'array']);

        $user = User::factory()->create();
        Post::factory()->create(['user_id' => $user->id]);

        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertOk()->assertJsonCount(1, 'data');

        $_SERVER['restify.user.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($user->getKey());
        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertNotFound();

        $_SERVER['restify.user.mainQueryCallback'] = null;
        $_SERVER['restify.users.show'] = false;
        $this->getJson(UserRepository::route("{$user->id}/posts"))->assertForbidden();
    }

    #[Test]
    public function each_parent_gets_its_own_cached_index(): void
    {
        $this->enableRepositoryCache();
        config(['cache.default' => 'array']);

        $first = User::factory()->create();
        $second = User::factory()->create();
        Post::factory()->create(['user_id' => $first->id, 'title' => 'First']);
        Post::factory()->create(['user_id' => $second->id, 'title' => 'Second']);

        $this->getJson(UserRepository::route("{$first->id}/posts"))->assertOk()->assertJsonPath('data.0.attributes.title', 'First');
        $this->getJson(UserRepository::route("{$second->id}/posts"))->assertOk()->assertJsonPath('data.0.attributes.title', 'Second');
    }

    #[Test]
    public function a_child_through_the_wrong_parent_is_not_found(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id, 'title' => 'Kept']);

        $this->putJson(UserRepository::route("{$other->id}/posts/{$post->id}"), ['title' => 'Changed'])->assertNotFound();
        $this->deleteJson(UserRepository::route("{$other->id}/posts/{$post->id}"))->assertNotFound();

        $this->assertSame('Kept', $post->fresh()->title);
    }

    #[Test]
    public function a_second_has_one_child_answers_409(): void
    {
        $user = User::factory()->create();

        $this->postJson(UserRepository::route("{$user->id}/post"), ['title' => 'First'])->assertCreated();
        $this->postJson(UserRepository::route("{$user->id}/post"), ['title' => 'Second'])->assertConflict();

        $this->assertSame(['First'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_null_parent_key_counts_as_not_sent(): void
    {
        $user = User::factory()->create();

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created', 'user_id' => null])->assertCreated();

        $this->assertSame(['Created'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_numeric_parent_key_matches_however_it_is_spelled(): void
    {
        $user = User::factory()->create();

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created', 'user_id' => "0{$user->id}"])->assertCreated();

        $this->assertSame(['Created'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function the_morph_type_is_accepted_as_alias_or_class_and_stored_as_the_alias(): void
    {
        Relation::morphMap(['post' => Post::class]);
        $post = Post::factory()->create();

        $this->postJson(PostRepository::route("{$post->id}/actionLogs"), ['name' => 'ByClass', 'batch_id' => 'batch', 'actionable_type' => Post::class])->assertCreated();
        $this->postJson(PostRepository::route("{$post->id}/actionLogs"), ['name' => 'ByAlias', 'batch_id' => 'batch', 'actionable_type' => 'post'])->assertCreated();
        $this->postJson(PostRepository::route("{$post->id}/actionLogs"), ['name' => 'Other', 'batch_id' => 'batch', 'actionable_type' => User::class])->assertUnprocessable();

        $this->assertSame(['post', 'post'], ActionLog::query()->whereIn('name', ['ByClass', 'ByAlias'])->pluck('actionable_type')->all());
        $this->assertSame(0, ActionLog::query()->where('name', 'Other')->count());
    }

    #[Test]
    public function the_child_validation_sees_the_parent_key(): void
    {
        $user = User::factory()->create();
        $_SERVER['restify.post.afterValidation.spy'] = function (Validator $validator): void {
            $_SERVER['relatable.validated.user_id'] = $validator->getData()['user_id'] ?? null;
        };

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created'])->assertCreated();

        $this->assertSame($user->id, $_SERVER['relatable.validated.user_id']);
        $this->assertSame(['Created'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_payload_key_for_another_parent_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created', 'user_id' => $other->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('posts', 0);

        $this->postJson(UserRepository::route("{$user->id}/posts"), ['title' => 'Created', 'user_id' => (string) $user->id])->assertCreated();

        $this->assertSame(['Created'], $user->posts()->pluck('title')->all());
    }

    #[Test]
    public function a_morph_child_validation_sees_the_parent_key_and_type(): void
    {
        $post = Post::factory()->create();

        $this->postJson(PostRepository::route("{$post->id}/actionLogs"), ['name' => 'Logged', 'batch_id' => 'batch'])->assertCreated();

        $log = ActionLog::query()->where('name', 'Logged')->sole();
        $this->assertSame($post->getMorphClass(), $log->actionable_type);
        $this->assertSame($post->id, (int) $log->actionable_id);

        $this->postJson(PostRepository::route("{$post->id}/actionLogs"), ['name' => 'Other', 'batch_id' => 'batch', 'actionable_type' => User::class])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('actionable_type');

        $this->assertSame(0, ActionLog::query()->where('name', 'Other')->count());
    }

    #[Test]
    public function a_many_to_many_child_cannot_be_created_through_the_parent(): void
    {
        $user = User::factory()->create();

        $this->postJson(UserRepository::route("{$user->id}/companies"), ['name' => 'Acme'])->assertBadRequest();
        $this->postJson(UserRepository::route("{$user->id}/roles"), ['name' => 'Admin'])->assertBadRequest();

        $this->assertSame(0, Company::query()->count());
        $this->assertSame(0, Role::query()->count());
    }

    /**
     * The callback returns a clone, so a caller that ignores the builder mainQuery returns still sees both.
     *
     * @return array{User, User}
     */
    private function hideUser(): array
    {
        $hidden = User::factory()->create();
        $visible = User::factory()->create();

        $_SERVER['restify.user.mainQueryCallback'] = fn ($query) => $query->clone()->whereKeyNot($hidden->getKey());

        return [$hidden, $visible];
    }
}

class PostActionLogRepository extends Repository
{
    public static $model = ActionLog::class;

    public static string $uriKey = 'actionLogs';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->storingRules('required'),
            field('batch_id')->storingRules('required'),
            field('actionable_type')->storingRules('required'),
            field('actionable_id')->storingRules('required'),
        ];
    }
}

class SinglePostRepository extends Repository
{
    public static $model = Post::class;

    public static string $uriKey = 'post';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class UserByEmail extends User
{
    protected $table = 'users';

    public function getRouteKeyName(): string
    {
        return 'email';
    }

    public function posts(): HasManyRelation
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class UserByEmailRepository extends Repository
{
    public static $model = UserByEmail::class;

    public static string $uriKey = 'users-by-email';

    public static function include(): array
    {
        return [
            HasMany::make('posts', PostRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}
