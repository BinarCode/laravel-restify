<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Request as McpToolRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Mergeable repository merges the model's array form into `attributes`. A relation loaded on the
 * model must not travel with it: related models are only serialized under `relationships`, by their
 * own repository.
 */
class MergeableRelatedSerializationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const OWNER_EMAIL = 'owner@mergeable-related.test';

    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            MergeableRelatedPostRepository::class,
            MergeableRelatedShapedPostRepository::class,
            MergeableRelatedCamelKeysPostRepository::class,
            MergeableRelatedSameNamePostRepository::class,
            MergeableRelatedEagerPostRepository::class,
            MergeableRelatedLazyFieldPostRepository::class,
            MergeableRelatedModelEagerPostRepository::class,
            MergeableRelatedAccessorPostRepository::class,
            MergeableRelatedCustomArrayPostRepository::class,
            MergeableRelatedStorePostRepository::class,
            MergeableRelatedUserRepository::class,
            MergeableRelatedCompanyRepository::class,
            MergeableRelatedMemberRepository::class,
        ]);
    }

    #[Test]
    #[TestWith(['related'])]
    #[TestWith(['include'])]
    public function it_keeps_a_requested_relation_out_of_the_index_attributes(string $parameter): void
    {
        $post = $this->createPost();

        $response = $this->getJson(MergeableRelatedPostRepository::route(query: [$parameter => 'user']))->assertOk();

        $this->assertSame(
            Arr::except($post->fresh()->toArray(), ['description']),
            $response->json('data.0.attributes'),
        );
        $this->assertSame(['name' => 'Owner'], $response->json('data.0.relationships.user.attributes'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_keeps_a_requested_has_many_relation_out_of_the_index_attributes(): void
    {
        $post = $this->createPost();

        Comment::factory()->count(2)->create(['post_id' => $post->id, 'user_id' => $post->user_id]);

        $response = $this->getJson(MergeableRelatedPostRepository::route(query: ['related' => 'comments']))->assertOk();

        $this->assertSame(
            Arr::except($post->fresh()->toArray(), ['description']),
            $response->json('data.0.attributes'),
        );
        $this->assertCount(2, $response->json('data.0.relationships.comments'));
        $this->assertSame(['comment'], array_keys($response->json('data.0.relationships.comments.0.attributes')));
    }

    #[Test]
    public function it_keeps_a_nested_relation_out_of_the_index_attributes(): void
    {
        $post = $this->createPost();

        Company::factory()->create()->users()->attach($post->user_id, ['is_admin' => true]);

        $response = $this->getJson(MergeableRelatedPostRepository::route(query: ['related' => 'user.companies']))->assertOk();

        $this->assertSame(
            Arr::except($post->fresh()->toArray(), ['description']),
            $response->json('data.0.attributes'),
        );
        $this->assertSame(['name' => 'Owner'], $response->json('data.0.relationships.user.attributes'));
        $this->assertCount(1, $response->json('data.0.relationships.user.relationships.companies'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    /**
     * @param  array<string, string>  $query
     */
    #[Test]
    #[TestWith([[]], 'without a relation')]
    #[TestWith([['related' => 'postAuthor']], 'with a requested relation')]
    public function it_merges_casts_appends_and_hidden_attributes_as_the_model_serializes_them(array $query): void
    {
        $post = $this->createPost(['is_active' => 1]);
        $model = MergeableRelatedShapedPost::query()->findOrFail($post->id);

        $attributes = $this->getJson(MergeableRelatedShapedPostRepository::route(query: $query))
            ->assertOk()
            ->json('data.0.attributes');

        $this->assertSame($model->toArray(), $attributes);
        $this->assertSame('MERGEABLE POST', $attributes['headline']);
        $this->assertTrue($attributes['is_active']);
        $this->assertArrayNotHasKey('image', $attributes);
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    #[Test]
    #[TestWith([MergeableRelatedShapedPostRepository::class], 'the model snake cases its relations')]
    #[TestWith([MergeableRelatedCamelKeysPostRepository::class], 'the model keeps the relation names')]
    public function it_keeps_a_camel_case_relation_out_of_the_index_attributes(string $repository): void
    {
        $this->createPost();

        $response = $this->getJson($repository::route(query: ['related' => 'postAuthor']))->assertOk();

        $this->assertArrayNotHasKey('postAuthor', $response->json('data.0.attributes'));
        $this->assertArrayNotHasKey('post_author', $response->json('data.0.attributes'));
        $this->assertSame(['name' => 'Owner'], $response->json('data.0.relationships.postAuthor.attributes'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_returns_the_model_attribute_when_a_loaded_relation_has_the_same_name(): void
    {
        $post = $this->createPost(['category' => 'news']);
        $post->forceFill(['edited_by' => $post->user_id])->save();

        $response = $this->getJson(MergeableRelatedSameNamePostRepository::route())->assertOk();

        $this->assertSame('news', $response->json('data.0.attributes.category'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    #[Test]
    #[TestWith([MergeableRelatedEagerPostRepository::class, false], 'the repository $with, on index')]
    #[TestWith([MergeableRelatedEagerPostRepository::class, true], 'the repository $with, on show')]
    #[TestWith([MergeableRelatedLazyFieldPostRepository::class, false], 'a lazy field, on index')]
    #[TestWith([MergeableRelatedLazyFieldPostRepository::class, true], 'a lazy field, on show')]
    #[TestWith([MergeableRelatedModelEagerPostRepository::class, false], 'the model $with, on index')]
    #[TestWith([MergeableRelatedModelEagerPostRepository::class, true], 'the model $with, on show')]
    public function it_keeps_an_always_eager_loaded_relation_out_of_the_attributes(string $repository, bool $show): void
    {
        $post = $this->createPost();

        $response = $this->getJson($repository::route($show ? $post : null))->assertOk();

        $this->assertSame(
            $post->fresh()->toArray(),
            $response->json($show ? 'data.attributes' : 'data.0.attributes'),
        );
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    #[TestWith([false], 'index')]
    #[TestWith([true], 'show')]
    public function it_keeps_a_relation_an_appended_accessor_loads_out_of_the_attributes(bool $show): void
    {
        $post = $this->createPost();

        $response = $this->getJson(MergeableRelatedAccessorPostRepository::route($show ? $post : null))->assertOk();

        $attributes = $response->json($show ? 'data.attributes' : 'data.0.attributes');

        $this->assertSame('Owner', $attributes['owner_name']);
        $this->assertArrayNotHasKey('user', $attributes);
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_still_applies_a_custom_to_array_of_the_model(): void
    {
        $this->createPost();

        $response = $this->getJson(MergeableRelatedCustomArrayPostRepository::route(query: ['related' => 'user']))->assertOk();

        $this->assertSame('Mergeable post by Owner', $response->json('data.0.attributes.summary'));
        $this->assertArrayNotHasKey('user', $response->json('data.0.attributes'));
        $this->assertSame(['name' => 'Owner'], $response->json('data.0.relationships.user.attributes'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_keeps_the_relation_a_belongs_to_field_associates_out_of_the_store_response(): void
    {
        $owner = $this->createOwner();

        $response = $this->postJson(MergeableRelatedStorePostRepository::route(), [
            'title' => 'Stored post',
            'user' => $owner->id,
        ])->assertCreated();

        $this->assertSame($owner->id, $response->json('data.attributes.user_id'));
        $this->assertArrayNotHasKey('user', $response->json('data.attributes'));
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_keeps_loaded_relations_out_of_a_mergeable_related_repository(): void
    {
        $owner = $this->createOwner();

        Company::factory()->create()->users()->attach($owner->id, ['is_admin' => true]);

        $response = $this->getJson(MergeableRelatedCompanyRepository::route(query: ['related' => 'users']))->assertOk();

        $member = $response->json('data.0.relationships.users.0');

        $this->assertSame('Owner', $member['attributes']['name']);
        $this->assertArrayNotHasKey('email', $member['attributes']);
        $this->assertArrayNotHasKey('pivot', $member['attributes']);
        $this->assertTrue((bool) $member['pivots']['is_admin']);
        $this->assertOwnerEmailIsNotExposed($response);
    }

    #[Test]
    public function it_keeps_a_requested_relation_out_of_the_mcp_index_attributes(): void
    {
        $post = $this->createPost();

        $result = (new IndexTool(MergeableRelatedPostRepository::class))
            ->handle(new McpToolRequest(['include' => 'user']))
            ->getStructuredContent();

        $this->assertSame(
            Arr::except($post->fresh()->toArray(), ['description']),
            data_get($result, 'data.0.attributes'),
        );
        $this->assertSame(['name' => 'Owner'], data_get($result, 'data.0.relationships.user.attributes'));
        $this->assertStringNotContainsString(self::OWNER_EMAIL, (string) json_encode($result));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPost(array $attributes = []): Post
    {
        return Post::factory()->create([
            'user_id' => $this->createOwner()->id,
            'title' => 'Mergeable post',
            'description' => 'Hidden by the repository',
            ...$attributes,
        ]);
    }

    private function createOwner(): User
    {
        return User::factory()->create([
            'name' => 'Owner',
            'email' => self::OWNER_EMAIL,
        ]);
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    private function assertOwnerEmailIsNotExposed(TestResponse $response): void
    {
        $this->assertStringNotContainsString(self::OWNER_EMAIL, (string) $response->getContent());
    }
}

class MergeableRelatedShapedPost extends Post
{
    protected $table = 'posts';

    protected $hidden = ['image'];

    protected $appends = ['headline'];

    public function getHeadlineAttribute(): string
    {
        return strtoupper($this->title);
    }

    public function postAuthor(): EloquentBelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Named after the `category` column on purpose.
     */
    public function category(): EloquentBelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}

class MergeableRelatedCamelKeysPost extends MergeableRelatedShapedPost
{
    public static $snakeAttributes = false;
}

class MergeableRelatedModelEagerPost extends Post
{
    protected $table = 'posts';

    protected $with = ['user'];
}

class MergeableRelatedAccessorPost extends Post
{
    protected $table = 'posts';

    protected $appends = ['owner_name'];

    public function getOwnerNameAttribute(): ?string
    {
        return $this->user?->name;
    }
}

class MergeableRelatedCustomArrayPost extends Post
{
    protected $table = 'posts';

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'summary' => "{$this->title} by {$this->user?->name}",
        ];
    }
}

class MergeableRelatedPostRepository extends Repository implements Mergeable
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mergeable-related-posts';

    public static function related(): array
    {
        return [
            'user' => BelongsTo::make('user', MergeableRelatedUserRepository::class),
            'comments' => HasMany::make('comments', CommentRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('description')->hidden(),
        ];
    }
}

class MergeableRelatedShapedPostRepository extends Repository implements Mergeable
{
    public static $model = MergeableRelatedShapedPost::class;

    public static string $uriKey = 'mergeable-related-shaped-posts';

    public static function related(): array
    {
        return [
            'postAuthor' => BelongsTo::make('postAuthor', MergeableRelatedUserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedCamelKeysPostRepository extends MergeableRelatedShapedPostRepository
{
    public static $model = MergeableRelatedCamelKeysPost::class;

    public static string $uriKey = 'mergeable-related-camel-keys-posts';
}

class MergeableRelatedSameNamePostRepository extends Repository implements Mergeable
{
    public static $model = MergeableRelatedShapedPost::class;

    public static string $uriKey = 'mergeable-related-same-name-posts';

    public static array $with = ['category'];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedEagerPostRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static string $uriKey = 'mergeable-related-eager-posts';

    public static array $with = ['user'];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedLazyFieldPostRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static string $uriKey = 'mergeable-related-lazy-field-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('owner', fn () => $this->resource->user?->name)->lazy('user'),
        ];
    }
}

class MergeableRelatedModelEagerPostRepository extends Repository implements Mergeable
{
    public static $model = MergeableRelatedModelEagerPost::class;

    public static string $uriKey = 'mergeable-related-model-eager-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedAccessorPostRepository extends Repository implements Mergeable
{
    public static $model = MergeableRelatedAccessorPost::class;

    public static string $uriKey = 'mergeable-related-accessor-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedCustomArrayPostRepository extends Repository implements Mergeable
{
    public static $model = MergeableRelatedCustomArrayPost::class;

    public static string $uriKey = 'mergeable-related-custom-array-posts';

    public static function related(): array
    {
        return [
            'user' => BelongsTo::make('user', MergeableRelatedUserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}

class MergeableRelatedStorePostRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static string $uriKey = 'mergeable-related-store-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            BelongsTo::make('user', MergeableRelatedUserRepository::class),
        ];
    }
}

class MergeableRelatedUserRepository extends Repository
{
    use HasMcpTools;

    public static $model = User::class;

    public static string $uriKey = 'mergeable-related-users';

    public static bool $globallySearchable = false;

    public static function related(): array
    {
        return [
            'companies' => BelongsToMany::make('companies', MergeableRelatedCompanyRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email')->canSee(fn () => false),
        ];
    }
}

class MergeableRelatedCompanyRepository extends Repository
{
    public static $model = Company::class;

    public static string $uriKey = 'mergeable-related-companies';

    public static bool $globallySearchable = false;

    public static function related(): array
    {
        return [
            'users' => BelongsToMany::make('users', MergeableRelatedMemberRepository::class)->withPivot(
                Field::make('is_admin'),
            ),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}

class MergeableRelatedMemberRepository extends Repository implements Mergeable
{
    public static $model = User::class;

    public static string $uriKey = 'mergeable-related-members';

    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email')->canSee(fn () => false),
        ];
    }
}
