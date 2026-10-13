<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings\DeclaredCacheSettingsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings\InheritedCacheSettingsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings\InheritingCacheSettingsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings\RedeclaringChildCacheSettingsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings\UndeclaredCacheSettingsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SanctumUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;

class RepositoryCacheIsolationTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        config(['cache.stores.secondary' => ['driver' => 'array']]);

        $this->enableRepositoryCache();
    }

    #[Test]
    public function the_same_user_id_on_different_guards_gets_different_cache_keys(): void
    {
        config(['auth.guards.admin' => ['driver' => 'session', 'provider' => 'users']]);

        $user = User::factory()->create();

        $this->actingAs($user, 'web');
        $webKey = $this->indexCacheKey();

        $this->actingAs($user, 'admin');
        $adminKey = $this->indexCacheKey();

        $this->assertNotSame($webKey, $adminKey);
        $morphClass = $user->getMorphClass();
        $this->assertStringContainsString("guard_web:model_{$morphClass}:user_{$user->getKey()}", $webKey);
        $this->assertStringContainsString("guard_admin:model_{$morphClass}:user_{$user->getKey()}", $adminKey);
    }

    #[Test]
    #[TestWith(['file'])]
    #[TestWith(['database'])]
    #[TestWith(['array'])]
    public function clear_cache_leaves_unrelated_keys_alone_and_serves_fresh_data(string $store): void
    {
        if ($store === 'database') {
            $this->createCacheTable();
        }

        config(['cache.default' => $store]);
        Cache::store($store)->flush();

        $post = Post::factory()->create();

        $this->getJson(PostRepository::route())->assertOk();
        $keyBefore = $this->indexCacheKey();
        $this->assertTrue($this->entryExists($keyBefore, $store), 'the index response should have been cached');

        Cache::put('unrelated-key', 'unrelated-value', 60);

        DB::table($post->getTable())->update(['title' => 'changed-without-events']);

        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonMissing(['title' => 'changed-without-events']);

        PostRepository::clearCache();

        $this->assertSame('unrelated-value', Cache::get('unrelated-key'));
        $this->assertNotSame($keyBefore, $this->indexCacheKey());

        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonFragment(['title' => 'changed-without-events']);
    }

    #[Test]
    public function clear_cache_only_invalidates_its_own_repository(): void
    {
        $this->getJson(PostRepository::route())->assertOk();
        $postsKey = $this->indexCacheKey();

        $companyVersionBefore = $this->versionOf(CompanyRepository::class);

        PostRepository::clearCache();

        $this->assertNotSame($postsKey, $this->indexCacheKey());
        $this->assertSame($companyVersionBefore, $this->versionOf(CompanyRepository::class));
    }

    #[Test]
    public function an_evicted_version_never_revives_entries_cached_under_an_older_one(): void
    {
        $this->getJson(PostRepository::route())->assertOk();
        $keyBefore = $this->indexCacheKey();

        PostRepository::clearCache();
        $keyAfterClear = $this->indexCacheKey();

        Cache::forget('restify:repository:posts:version');

        $this->assertNotSame($keyBefore, $this->indexCacheKey());
        $this->assertNotSame($keyAfterClear, $this->indexCacheKey());
    }

    #[Test]
    public function the_cache_ttl_setter_only_changes_its_own_repository(): void
    {
        PostRepository::cacheTtl(60);

        $this->assertSame(60, $this->ttlOf(PostRepository::class));
        $this->assertSame(300, $this->ttlOf(CompanyRepository::class));
    }

    #[Test]
    public function the_enable_and_disable_setters_only_change_their_own_repository(): void
    {
        PostRepository::disableCache();

        $this->assertFalse($this->isEnabled(PostRepository::class));
        $this->assertTrue($this->isEnabled(CompanyRepository::class));

        PostRepository::enableCache();

        $this->assertTrue($this->isEnabled(PostRepository::class));
    }

    #[Test]
    public function a_disabled_repository_does_not_cache(): void
    {
        PostRepository::disableCache();

        $this->getJson(PostRepository::route())->assertOk();

        $this->assertFalse($this->entryExists($this->indexCacheKey(), 'array'));
    }

    #[Test]
    public function the_cache_tags_setter_only_changes_its_own_repository_and_never_duplicates(): void
    {
        PostRepository::cacheTags(['content']);
        PostRepository::cacheTags(['content', 'blog']);

        $this->assertSame(['restify', 'repositories', 'posts', 'content', 'blog'], $this->tagsOf(PostRepository::class));
        $this->assertSame(['restify', 'repositories', 'companies'], $this->tagsOf(CompanyRepository::class));
    }

    #[Test]
    public function the_default_tags_do_not_grow_when_a_repository_is_booted_again(): void
    {
        PostRepository::resolveWith(new Post);
        PostRepository::resolveWith(new Post);

        $this->assertSame(['restify', 'repositories', 'posts'], $this->tagsOf(PostRepository::class));
        $this->assertSame([], PostRepository::$cacheTags);
    }

    #[Test]
    public function the_cache_store_setter_only_changes_its_own_repository(): void
    {
        PostRepository::cacheStore('secondary');

        $this->getJson(PostRepository::route())->assertOk();

        $this->assertTrue($this->entryExists($this->indexCacheKey(), 'secondary'));
        $this->assertFalse($this->entryExists($this->indexCacheKey(), 'array'));
        $this->assertNull($this->storeNameOf(CompanyRepository::class));
    }

    #[Test]
    public function properties_redeclared_by_a_repository_keep_applying_to_that_repository_only(): void
    {
        $this->assertSame(600, $this->ttlOf(DeclaredCacheSettingsRepository::class));
        $this->assertFalse($this->isEnabled(DeclaredCacheSettingsRepository::class));
        $this->assertSame('secondary', $this->storeNameOf(DeclaredCacheSettingsRepository::class));
        $this->assertSame(
            ['restify', 'repositories', 'declared-cache-settings', 'declared'],
            $this->tagsOf(DeclaredCacheSettingsRepository::class),
        );

        $this->assertSame(300, $this->ttlOf(UndeclaredCacheSettingsRepository::class));
        $this->assertTrue($this->isEnabled(UndeclaredCacheSettingsRepository::class));
        $this->assertNull($this->storeNameOf(UndeclaredCacheSettingsRepository::class));
    }

    #[Test]
    public function a_setter_beats_a_redeclared_property(): void
    {
        DeclaredCacheSettingsRepository::cacheTtl(30);
        DeclaredCacheSettingsRepository::enableCache();
        DeclaredCacheSettingsRepository::cacheStore(null);

        $this->assertSame(30, $this->ttlOf(DeclaredCacheSettingsRepository::class));
        $this->assertTrue($this->isEnabled(DeclaredCacheSettingsRepository::class));
        $this->assertNull($this->storeNameOf(DeclaredCacheSettingsRepository::class));
    }

    #[Test]
    public function the_configured_ttl_applies_to_a_repository_that_sets_none(): void
    {
        $this->setRepositoryCacheTtl(42);

        $this->assertSame(42, $this->ttlOf(UndeclaredCacheSettingsRepository::class));
        $this->assertSame(42, $this->ttlOf(PostRepository::class));
        $this->assertSame(600, $this->ttlOf(DeclaredCacheSettingsRepository::class));

        PostRepository::cacheTtl(7);

        $this->assertSame(7, $this->ttlOf(PostRepository::class));
    }

    #[Test]
    public function the_configured_ttl_is_the_one_the_entry_is_cached_with(): void
    {
        $this->setRepositoryCacheTtl(30);

        $this->getJson(PostRepository::route())->assertOk();
        $key = $this->indexCacheKey();

        $this->travel(29)->seconds();
        $this->assertTrue($this->entryExists($key, 'array'));

        $this->travel(2)->seconds();
        $this->assertFalse($this->entryExists($key, 'array'));
    }

    #[Test]
    public function the_index_still_works_on_a_store_that_keeps_nothing(): void
    {
        config(['cache.default' => 'null']);

        Post::factory()->create(['title' => 'on-null-store']);

        $this->recordQueries();

        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonFragment(['title' => 'on-null-store']);

        $this->assertNotEmpty($this->selectsAgainst(Post::class));
    }

    #[Test]
    public function requests_differing_only_in_relatable_per_page_get_different_cache_keys(): void
    {
        $base = $this->indexCacheKey();
        $scalar = $this->indexCacheKey(['relatablePerPage' => 5]);
        $perRelation = $this->indexCacheKey(['relatablePerPage' => ['posts' => 5]]);
        $otherRelation = $this->indexCacheKey(['relatablePerPage' => ['posts' => 10]]);

        $this->assertCount(4, array_unique([$base, $scalar, $perRelation, $otherRelation]));
    }

    #[Test]
    public function the_order_of_the_relatable_per_page_entries_does_not_change_the_cache_key(): void
    {
        $this->assertSame(
            $this->indexCacheKey(['relatablePerPage' => ['posts' => 5, 'users' => 3]]),
            $this->indexCacheKey(['relatablePerPage' => ['users' => 3, 'posts' => 5]]),
        );
    }

    #[Test]
    public function the_same_guard_and_id_on_different_user_models_get_different_cache_keys(): void
    {
        $user = User::factory()->create(['id' => 5]);
        $sanctumUser = new SanctumUser;
        $sanctumUser->id = 5;

        $this->actingAs($user);
        $userKey = $this->indexCacheKey();

        $this->actingAs($sanctumUser);
        $sanctumKey = $this->indexCacheKey();

        $this->assertNotSame($userKey, $sanctumKey);
    }

    #[Test]
    public function clear_cache_invalidates_a_repository_whose_cache_is_disabled_so_re_enabling_serves_fresh_data(): void
    {
        $this->getJson(PostRepository::route())->assertOk();
        $keyBefore = $this->indexCacheKey();

        PostRepository::disableCache();
        PostRepository::clearCache();
        PostRepository::enableCache();

        $this->assertNotSame($keyBefore, $this->indexCacheKey());
    }

    #[Test]
    public function a_direct_write_to_the_shared_ttl_property_is_still_honored(): void
    {
        PostRepository::$cacheTtl = 60;

        $this->assertSame(60, $this->ttlOf(PostRepository::class));
        $this->assertSame(60, $this->ttlOf(CompanyRepository::class));

        $this->setRepositoryCacheTtl(42);
        $this->assertSame(60, $this->ttlOf(PostRepository::class));

        PostRepository::cacheTtl(7);
        $this->assertSame(7, $this->ttlOf(PostRepository::class));
    }

    #[Test]
    public function a_setter_on_a_base_repository_applies_to_its_children_and_a_child_wins(): void
    {
        InheritingCacheSettingsRepository::cacheTtl(90);
        InheritingCacheSettingsRepository::disableCache();
        InheritingCacheSettingsRepository::cacheStore('secondary');
        InheritingCacheSettingsRepository::cacheTags(['shared']);

        $this->assertSame(90, $this->ttlOf(InheritedCacheSettingsRepository::class));
        $this->assertFalse($this->isEnabled(InheritedCacheSettingsRepository::class));
        $this->assertSame('secondary', $this->storeNameOf(InheritedCacheSettingsRepository::class));
        $this->assertContains('shared', $this->tagsOf(InheritedCacheSettingsRepository::class));
        $this->assertSame(300, $this->ttlOf(PostRepository::class));

        InheritedCacheSettingsRepository::cacheTtl(15);
        $this->assertSame(15, $this->ttlOf(InheritedCacheSettingsRepository::class));
        $this->assertSame(90, $this->ttlOf(InheritingCacheSettingsRepository::class));

        $this->assertSame(600, $this->ttlOf(RedeclaringChildCacheSettingsRepository::class));
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    private function ttlOf(string $repository): int
    {
        return (new ReflectionMethod($repository, 'resolveCacheTtl'))->invoke(null);
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    private function isEnabled(string $repository): bool
    {
        return (new ReflectionMethod($repository, 'cacheIsEnabled'))->invoke(null);
    }

    /**
     * @param  class-string<Repository>  $repository
     * @return array<int, string>
     */
    private function tagsOf(string $repository): array
    {
        return (new ReflectionMethod($repository, 'resolveCacheTags'))->invoke(null);
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    private function storeNameOf(string $repository): ?string
    {
        return (new ReflectionMethod($repository, 'resolveCacheStoreName'))->invoke(null);
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    private function versionOf(string $repository): string
    {
        return (new ReflectionMethod($repository, 'cacheVersion'))->invoke(null);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function indexCacheKey(array $query = []): string
    {
        $request = RestifyRequest::create('/api/restify/posts', 'GET', $query);
        $request->setUserResolver(fn (?string $guard = null) => Auth::guard($guard)->user());

        return PostRepository::resolveWith(new Post)->generateIndexCacheKey($request);
    }

    private function entryExists(string $key, string $storeName): bool
    {
        $store = Cache::store($storeName);

        return PostRepository::cacheStoreSupportsTagging($store)
            ? $store->tags($this->tagsOf(PostRepository::class))->has($key)
            : $store->has($key);
    }

    private function createCacheTable(): void
    {
        if (Schema::hasTable('cache')) {
            return;
        }

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->unique();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }
}
