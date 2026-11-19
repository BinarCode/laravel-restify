<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Laravel\Sanctum\SanctumServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * The repository a request runs against is a fact about the route it matched: the `{repository}` segment, or the
 * repository a prefixed or public route was registered for. Those literal routes have no `{repository}` segment,
 * and the repository used to be picked from the request instead: from an input key, and failing that from the first
 * registered repository whose path appeared anywhere in the URI, query string included. A request to a public,
 * prefixed or custom repository route could therefore be answered by another repository. These pin that nothing
 * the client sends moves a route to another repository.
 */
class RepositoryResolvedFromRouteTest extends IntegrationTestCase
{
    private const MEMBER_EMAIL = 'member@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.middleware', [
            'api',
            'auth:sanctum',
            DispatchRestifyStartingEvent::class,
            AuthorizeRestify::class,
        ]);

        // The members repository is registered first: the order must not decide which repository a route serves.
        Restify::repositories([
            RouteMembersRepository::class,
            RoutePublicPostRepository::class,
            RoutePrefixedPostRepository::class,
            RouteCustomRoutePostRepository::class,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            SanctumServiceProvider::class,
        ]);
    }

    #[Test]
    public function a_guest_reaches_the_public_repository_and_nothing_else(): void
    {
        $post = $this->postSharingItsKeyWithAMember();

        $this->logout();

        $this->getJson(RoutePublicPostRepository::route())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'posts');

        $this->getJson(RoutePublicPostRepository::route($post))
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'A post');

        $this->getJson(RouteMembersRepository::route())->assertUnauthorized();
        $this->getJson(RouteMembersRepository::route($post))->assertUnauthorized();
        $this->getJson(RouteMembersRepository::route('getters/route-member-emails'))->assertUnauthorized();
        $this->postJson(RoutePublicPostRepository::route(), ['title' => 'A guest post'])->assertUnauthorized();
    }

    #[Test]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    #[TestWith(['next=/api/restify/route-members&mcp_repository_key=route-members'], 'both')]
    public function a_guest_lists_the_public_repository_whatever_the_query_string_names(string $query): void
    {
        $this->postSharingItsKeyWithAMember();

        $this->logout();

        $this->getJson(RoutePublicPostRepository::route().'?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'posts')
            ->assertJsonPath('data.0.attributes.title', 'A post')
            ->assertDontSee(self::MEMBER_EMAIL);
    }

    #[Test]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    public function a_guest_is_shown_the_public_record_whatever_the_query_string_names(string $query): void
    {
        $post = $this->postSharingItsKeyWithAMember();

        $this->logout();

        $this->getJson(RoutePublicPostRepository::route($post).'?'.$query)
            ->assertOk()
            ->assertJsonPath('data.type', 'posts')
            ->assertJsonPath('data.attributes.title', 'A post')
            ->assertDontSee(self::MEMBER_EMAIL);
    }

    #[Test]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    public function a_guest_runs_only_the_getters_of_the_public_repository(string $query): void
    {
        $post = $this->postSharingItsKeyWithAMember();

        $this->logout();

        $this->getJson(RoutePublicPostRepository::route('getters').'?'.$query)
            ->assertOk()
            ->assertJsonPath('data.0.uriKey', 'posts-index-getter')
            ->assertDontSee('route-member-emails');

        $this->getJson(RoutePublicPostRepository::getter(PostsIndexGetter::class).'?'.$query)
            ->assertOk()
            ->assertJsonPath('message', 'it works');

        $this->getJson(RoutePublicPostRepository::getter(PostsShowGetter::class, $post->getKey()).'?'.$query)
            ->assertOk()
            ->assertJsonPath('message', 'show works');

        $this->getJson(RoutePublicPostRepository::route('getters/route-member-emails').'?'.$query)
            ->assertNotFound()
            ->assertDontSee(self::MEMBER_EMAIL);

        $this->getJson(RoutePublicPostRepository::route($post->getKey().'/getters/route-member-email').'?'.$query)
            ->assertNotFound()
            ->assertDontSee(self::MEMBER_EMAIL);
    }

    #[Test]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    public function a_prefixed_route_reads_its_own_repository_whatever_the_query_string_names(string $query): void
    {
        $post = $this->postSharingItsKeyWithAMember();

        $this->getJson(RoutePrefixedPostRepository::route().'?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'posts')
            ->assertDontSee(self::MEMBER_EMAIL);

        $this->getJson(RoutePrefixedPostRepository::route($post).'?'.$query)
            ->assertOk()
            ->assertJsonPath('data.type', 'posts')
            ->assertJsonPath('data.attributes.title', 'A post')
            ->assertDontSee(self::MEMBER_EMAIL);

        $this->getJson(RoutePrefixedPostRepository::getter(PostsIndexGetter::class).'?'.$query)
            ->assertOk()
            ->assertJsonPath('message', 'it works');

        $this->getJson(RoutePrefixedPostRepository::route('getters/route-member-emails').'?'.$query)
            ->assertNotFound()
            ->assertDontSee(self::MEMBER_EMAIL);
    }

    #[Test]
    public function a_prefixed_route_writes_to_its_own_repository_whatever_the_body_names(): void
    {
        $post = $this->postSharingItsKeyWithAMember();

        $this->postJson(RoutePrefixedPostRepository::route(), [
            'title' => 'Stored through the prefix',
            'name' => 'Stored member',
            'email' => 'stored@example.com',
            'mcp_repository_key' => 'route-members',
        ])->assertCreated()->assertJsonPath('data.type', 'posts');

        $this->putJson(RoutePrefixedPostRepository::route($post), [
            'title' => 'Updated through the prefix',
            'name' => 'Updated member',
            'mcp_repository_key' => 'route-members',
        ])->assertOk()->assertJsonPath('data.type', 'posts');

        $this->assertDatabaseHas(Post::class, ['title' => 'Stored through the prefix']);
        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Updated through the prefix']);
        $this->assertDatabaseHas(User::class, ['id' => $post->getKey(), 'name' => 'A member']);
        $this->assertDatabaseMissing(User::class, ['email' => 'stored@example.com']);

        $this->deleteJson(RoutePrefixedPostRepository::route($post).'?mcp_repository_key=route-members')
            ->assertNoContent();

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
        $this->assertDatabaseHas(User::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function the_repository_segment_of_a_default_route_still_names_the_repository(): void
    {
        $this->postSharingItsKeyWithAMember();

        $this->getJson(PostRepository::route().'?mcp_repository_key=route-members')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'posts');

        $this->getJson(RouteMembersRepository::route().'?mcp_repository_key=posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'users')
            ->assertJsonPath('data.0.attributes.email', self::MEMBER_EMAIL);
    }

    #[Test]
    #[TestWith([''], 'nothing')]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    public function a_custom_repository_route_resolves_its_own_repository_whatever_the_query_string_names(string $query): void
    {
        $this->getJson(RouteCustomRoutePostRepository::route('resolved-repository').'?'.$query)
            ->assertOk()
            ->assertJsonPath('repository', 'route-custom-route-posts');
    }

    #[Test]
    #[TestWith(['mcp_repository_key=route-members'], 'the key the MCP tools used to send')]
    #[TestWith(['next=/api/restify/route-members'], 'the path of another repository')]
    public function a_request_without_a_route_does_not_take_its_repository_from_the_input(string $query): void
    {
        $request = RestifyRequest::create('/somewhere-else?'.$query);

        $this->expectExceptionMessage('Repository key missing.');

        $request->repository();
    }

    #[Test]
    public function a_request_bound_to_a_repository_by_the_server_resolves_it_over_the_input(): void
    {
        $request = RestifyRequest::create('/somewhere-else?mcp_repository_key=route-members&next=/api/restify/route-members');

        $this->assertSame('posts', $request->forRepository('posts')->repository()::uriKey());
    }

    private function postSharingItsKeyWithAMember(): Post
    {
        $post = Post::factory()->create(['user_id' => null, 'title' => 'A post']);

        User::factory()->create(['id' => $post->getKey(), 'name' => 'A member', 'email' => self::MEMBER_EMAIL]);

        return $post;
    }
}

class RouteMembersRepository extends Repository
{
    public static $model = User::class;

    public static string $uriKey = 'route-members';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
        ];
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            new RouteMemberEmailsGetter,
            (new RouteMemberEmailGetter)->onlyOnShow(),
        ];
    }
}

class RoutePublicPostRepository extends Repository
{
    public static bool|array $public = true;

    public static $model = Post::class;

    public static string $uriKey = 'route-public-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            PostsIndexGetter::make(),
            PostsShowGetter::make()->onlyOnShow(),
        ];
    }
}

class RoutePrefixedPostRepository extends Repository
{
    public static $prefix = 'api/v1';

    public static $model = Post::class;

    public static string $uriKey = 'route-prefixed-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            PostsIndexGetter::make(),
        ];
    }
}

class RouteCustomRoutePostRepository extends Repository
{
    public static $model = Post::class;

    public static string $uriKey = 'route-custom-route-posts';

    public static function routes(Router $router, array $attributes, $wrap = true)
    {
        $router->get('resolved-repository', fn (RestifyRequest $request): JsonResponse => response()->json([
            'repository' => $request->repository()::uriKey(),
        ]));
    }
}

class RouteMemberEmailsGetter extends Getter
{
    public static $uriKey = 'route-member-emails';

    public function handle(Request $request): JsonResponse
    {
        return response()->json(['emails' => User::query()->pluck('email')]);
    }
}

class RouteMemberEmailGetter extends Getter
{
    public static $uriKey = 'route-member-email';

    public function handle(Request $request, ?Model $model = null): JsonResponse
    {
        return response()->json(['email' => $model?->getAttribute('email')]);
    }
}
