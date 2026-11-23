<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Bootstrap\RoutesBoot;
use Binaryk\LaravelRestify\Bootstrap\RoutesDefinition;
use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\CachesRoutes;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Laravel\Sanctum\SanctumServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Routes can only be cached while every route has a name of its own. A prefixed repository (`$prefix`) and a public
 * one (`$public`) get routes that repeat the default `{repository}` routes for that one repository, and those used
 * to repeat the names as well, so `artisan route:cache` failed for any application with such a repository. These
 * pin that the routes cache with every mix of repositories, that a cached route answers like the route it was built
 * from, and that the default routes are still named the way applications refer to them.
 */
class RouteCacheTest extends IntegrationTestCase
{
    use CachesRoutes;

    /**
     * The routes every repository has, by the end of their name. The default routes match any repository; a
     * prefixed or public repository gets the same list with its own key in place of `{repository}`.
     */
    private const REPOSITORY_ROUTES = [
        'filters.index' => 'GET|HEAD {repository}/filters',
        'filters.apply' => 'POST {repository}/apply-restify-advanced-filters',
        'actions.index' => 'GET|HEAD {repository}/actions',
        'actions.repository.index' => 'GET|HEAD {repository}/{repositoryId}/actions',
        'actions.perform' => 'POST {repository}/action',
        'actions.performs' => 'POST {repository}/actions',
        'actions.repository.perform' => 'POST {repository}/{repositoryId}/action',
        'actions.repository.performs' => 'POST {repository}/{repositoryId}/actions',
        'getters.index' => 'GET|HEAD {repository}/getters',
        'getters.repository.index' => 'GET|HEAD {repository}/{repositoryId}/getters',
        'getters.perform' => 'GET|HEAD {repository}/getters/{getter}',
        'getters.repository.perform' => 'GET|HEAD {repository}/{repositoryId}/getters/{getter}',
        'ai-instructions' => 'GET|HEAD {repository}/ai-instructions',
        'index' => 'GET|HEAD {repository}',
        'store' => 'POST {repository}',
        'store.bulk' => 'POST {repository}/bulk',
        'update.bulk' => 'POST {repository}/bulk/update',
        'destroy.bulk' => 'DELETE {repository}/bulk/delete',
        'show' => 'GET|HEAD {repository}/{repositoryId}',
        'patch' => 'PATCH {repository}/{repositoryId}',
        'put' => 'PUT {repository}/{repositoryId}',
        'update' => 'POST {repository}/{repositoryId}',
        'destroy' => 'DELETE {repository}/{repositoryId}',
    ];

    /**
     * The routes that exist once, next to the default routes.
     */
    private const DEFAULT_ONLY_ROUTES = [
        'search' => 'GET|HEAD search',
        'profile' => 'GET|HEAD profile',
        'profile.updatePut' => 'PUT profile',
        'profile.updatePost' => 'POST profile',
        'restifyjs.setup' => 'GET|HEAD restifyjs/setup',
        'field.destroy' => 'DELETE {repository}/{repositoryId}/field/{field}',
        'attach' => 'POST {repository}/{repositoryId}/attach/{relatedRepository}',
        'detach' => 'POST {repository}/{repositoryId}/detach/{relatedRepository}',
        'sync' => 'POST {repository}/{repositoryId}/sync/{relatedRepository}',
        'relatable.index' => 'GET|HEAD {parentRepository}/{parentRepositoryId}/{repository}',
        'relatable.store' => 'POST {parentRepository}/{parentRepositoryId}/{repository}',
        'relatable.show' => 'GET|HEAD {parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
        'relatable.update' => 'POST {parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
        'relatable.updatePut' => 'PUT {parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
        'relatable.destroy' => 'DELETE {parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.middleware', [
            'api',
            'auth:sanctum',
            DispatchRestifyStartingEvent::class,
            AuthorizeRestify::class,
        ]);

        // Several repositories of each kind, on top of the default ones every test starts with.
        Restify::repositories([
            RouteCacheMembersRepository::class,
            RouteCachePublicPostRepository::class,
            RouteCachePublicArticleRepository::class,
            RouteCachePrefixedPostRepository::class,
            RouteCachePrefixedArticleRepository::class,
            RouteCachePublicPrefixedPostRepository::class,
            RouteCacheFiltersRepository::class,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            SanctumServiceProvider::class,
        ]);
    }

    #[Test]
    public function every_route_has_a_name_of_its_own(): void
    {
        app(RoutesBoot::class)->boot();

        $names = array_map(fn (Route $route): ?string => $route->getName(), Router::getRoutes()->getRoutes());

        $this->assertNotContains(null, $names);
        $this->assertSame([], array_keys(array_filter(
            array_count_values($names),
            fn (int $routes): bool => $routes > 1
        )));
    }

    #[Test]
    public function the_default_routes_keep_their_names(): void
    {
        app(RoutesBoot::class)->boot();

        $this->assertSame(
            self::routes('restify.api.', 'api/restify', '{repository}', self::REPOSITORY_ROUTES + self::DEFAULT_ONLY_ROUTES),
            $this->registeredRoutes(fn (string $name): bool => str_starts_with($name, 'restify.api.')
                && ! str_starts_with($name, 'restify.api.prefixed.')
                && ! str_starts_with($name, 'restify.api.public.'))
        );
    }

    /**
     * The last row is a repository whose key is also the start of a default route name: its routes are told apart
     * from `restify.api.filters.index` by the `prefixed` segment.
     */
    #[Test]
    #[TestWith(['prefixed', 'route-cache-prefixed-posts', 'api/v1'])]
    #[TestWith(['prefixed', 'route-cache-prefixed-articles', 'api/v2'])]
    #[TestWith(['public', 'route-cache-public-posts', 'api/restify'])]
    #[TestWith(['public', 'route-cache-public-articles', 'api/restify'])]
    #[TestWith(['prefixed', 'filters', 'api/v1'])]
    public function a_prefixed_or_public_repository_gets_route_names_of_its_own(string $kind, string $uriKey, string $base): void
    {
        app(RoutesBoot::class)->boot();

        $name = "restify.api.$kind.$uriKey.";

        $this->assertSame(
            self::routes($name, $base, $uriKey, self::REPOSITORY_ROUTES),
            $this->registeredRoutes(fn (string $registered): bool => str_starts_with($registered, $name))
        );
    }

    /**
     * While the names were shared, a default route name could generate the URL of a prefixed or public repository.
     */
    #[Test]
    #[TestWith([false], 'registered')]
    #[TestWith([true], 'cached')]
    public function a_route_name_generates_the_url_of_its_own_route(bool $cached): void
    {
        app(RoutesBoot::class)->boot();

        // The framework does this once the application has booted.
        Router::getRoutes()->refreshNameLookups();

        if ($cached) {
            $this->cacheRoutes();
        }

        $this->assertSame('/api/restify/posts', route('restify.api.index', ['repository' => 'posts'], false));
        $this->assertSame('/api/restify/posts/7', route('restify.api.show', [
            'repository' => 'posts',
            'repositoryId' => 7,
        ], false));
        $this->assertSame('/api/restify/route-cache-public-posts/actions', route('restify.api.actions.index', [
            'repository' => 'route-cache-public-posts',
        ], false));

        $this->assertSame(
            '/api/v1/route-cache-prefixed-posts/7',
            route('restify.api.prefixed.route-cache-prefixed-posts.show', ['repositoryId' => 7], false)
        );
        $this->assertSame(
            '/api/v2/route-cache-prefixed-articles',
            route('restify.api.prefixed.route-cache-prefixed-articles.index', [], false)
        );
        $this->assertSame(
            '/api/restify/route-cache-public-posts/7',
            route('restify.api.public.route-cache-public-posts.show', ['repositoryId' => 7], false)
        );
    }

    #[Test]
    public function the_routes_are_loaded_back_from_the_cache_as_they_were_registered(): void
    {
        app(RoutesBoot::class)->boot();

        $registered = $this->routeDetails();

        $this->cacheRoutes();

        $this->assertInstanceOf(CompiledRouteCollection::class, Router::getRoutes());
        $this->assertSame($registered, $this->routeDetails());
        $this->assertCount(
            count(self::DEFAULT_ONLY_ROUTES) + 8 * count(self::REPOSITORY_ROUTES),
            $this->registeredRoutes(fn (string $name): bool => str_starts_with($name, 'restify.api.')),
            'The default routes, and the routes of four prefixed and three public repositories.'
        );
    }

    #[Test]
    public function a_cached_route_keeps_its_repository_and_the_middleware_it_leaves_out(): void
    {
        $this->cacheRoutes();

        $routes = Router::getRoutes();
        $authenticate = Router::getMiddleware()['auth'].':sanctum';

        foreach (['index', 'show', 'getters.index', 'getters.perform', 'getters.repository.index', 'getters.repository.perform'] as $name) {
            $route = $routes->getByName('restify.api.public.route-cache-public-posts.'.$name);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame('route-cache-public-posts', $route->getAction(RoutesDefinition::REPOSITORY));
            $this->assertSame(['auth:sanctum'], $route->excludedMiddleware());
            $this->assertNotContains($authenticate, Router::gatherRouteMiddleware($route));
        }

        foreach (['public.route-cache-public-posts.store', 'prefixed.route-cache-prefixed-posts.index'] as $name) {
            $route = $routes->getByName('restify.api.'.$name);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(explode('.', $name)[1], $route->getAction(RoutesDefinition::REPOSITORY));
            $this->assertSame([], $route->excludedMiddleware());
            $this->assertContains($authenticate, Router::gatherRouteMiddleware($route));
        }

        $default = $routes->getByName('restify.api.index');

        $this->assertInstanceOf(Route::class, $default);
        $this->assertNull($default->getAction(RoutesDefinition::REPOSITORY));
        $this->assertContains($authenticate, Router::gatherRouteMiddleware($default));
    }

    #[Test]
    #[TestWith(['registered'], 'registered on each request')]
    #[TestWith(['cached'], 'the route cache alone')]
    #[TestWith(['cached and registered'], 'the route cache, next to the routes registered on each request')]
    public function a_user_is_answered_the_same_from_the_route_cache(string $routes): void
    {
        $post = Post::factory()->create(['user_id' => null, 'title' => 'A post']);

        User::factory()->create(['name' => 'A member']);

        $this->serveRoutes($routes);

        $this->getJson(RouteCacheMembersRepository::route())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'A member');

        $this->getJson(PostRepository::route($post))
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'A post');

        $this->getJson(RouteCachePrefixedPostRepository::route())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.title', 'A post');

        $this->getJson(RouteCachePrefixedArticleRepository::route($post))
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'A post');

        $this->getJson(RouteCachePrefixedPostRepository::getter(PostsIndexGetter::class))
            ->assertOk()
            ->assertJsonPath('message', 'it works');

        $this->postJson(RouteCachePrefixedPostRepository::route(), ['title' => 'Stored under the prefix'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.title', 'Stored under the prefix');

        $this->postJson(RouteCachePublicPostRepository::route(), ['title' => 'Stored in the public repository'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.title', 'Stored in the public repository');

        // A prefixed repository answers under its prefix only.
        $this->getJson(Restify::path(RouteCachePrefixedPostRepository::uriKey()))->assertForbidden();
    }

    #[Test]
    #[TestWith(['registered'], 'registered on each request')]
    #[TestWith(['cached'], 'the route cache alone')]
    #[TestWith(['cached and registered'], 'the route cache, next to the routes registered on each request')]
    public function a_guest_is_answered_the_same_from_the_route_cache(string $routes): void
    {
        $post = Post::factory()->create(['user_id' => null, 'title' => 'A post']);

        $this->serveRoutes($routes);

        $this->logout();

        $this->getJson(RouteCachePublicPostRepository::route())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.title', 'A post');

        $this->getJson(RouteCachePublicArticleRepository::route($post))
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'A post');

        $this->getJson(RouteCachePublicPostRepository::getter(PostsIndexGetter::class))
            ->assertOk()
            ->assertJsonPath('message', 'it works');

        // Only the index, show and getter routes of a public repository are open to a guest.
        $this->postJson(RouteCachePublicPostRepository::route(), ['title' => 'A guest post'])->assertUnauthorized();
        $this->getJson(RouteCacheMembersRepository::route())->assertUnauthorized();
        $this->getJson(PostRepository::route($post))->assertUnauthorized();
        $this->getJson(RouteCachePrefixedPostRepository::route())->assertUnauthorized();
    }

    private function serveRoutes(string $routes): void
    {
        match ($routes) {
            'registered' => null,
            'cached' => $this->cacheRoutes(),
            'cached and registered' => $this->cacheRoutes(cachedRoutesOnly: false),
        };
    }

    /**
     * @param  array<string, string>  $routes
     * @return array<string, string>
     */
    private static function routes(string $name, string $base, string $repository, array $routes): array
    {
        $named = [];

        foreach ($routes as $suffix => $route) {
            [$methods, $path] = explode(' ', $route);

            $named[$name.$suffix] = $methods.' '.$base.'/'.str_replace('{repository}', $repository, $path);
        }

        ksort($named);

        return $named;
    }

    /**
     * The registered routes with a matching name, as `name => 'METHODS uri'`.
     *
     * @param  callable(string): bool  $named
     * @return array<string, string>
     */
    private function registeredRoutes(callable $named): array
    {
        $routes = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();

            if ($named($name)) {
                $routes[$name] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        ksort($routes);

        return $routes;
    }

    /**
     * Everything about a route a request depends on, by route name.
     *
     * @return array<string, array<string, mixed>>
     */
    private function routeDetails(): array
    {
        $details = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $details[(string) $route->getName()] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'controller' => $route->getActionName(),
                'repository' => $route->getAction(RoutesDefinition::REPOSITORY),
                'middleware' => $route->gatherMiddleware(),
                'excluded middleware' => $route->excludedMiddleware(),
            ];
        }

        ksort($details);

        return $details;
    }
}

class RouteCacheMembersRepository extends Repository
{
    public static $model = User::class;

    public static string $uriKey = 'route-cache-members';

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}

class RouteCachePublicPostRepository extends Repository
{
    public static bool|array $public = true;

    public static $model = Post::class;

    public static string $uriKey = 'route-cache-public-posts';

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

class RouteCachePublicArticleRepository extends RouteCachePublicPostRepository
{
    public static string $uriKey = 'route-cache-public-articles';
}

class RouteCachePrefixedPostRepository extends Repository
{
    public static $prefix = 'api/v1';

    public static $model = Post::class;

    public static string $uriKey = 'route-cache-prefixed-posts';

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

class RouteCachePrefixedArticleRepository extends RouteCachePrefixedPostRepository
{
    public static $prefix = 'api/v2';

    public static string $uriKey = 'route-cache-prefixed-articles';
}

/**
 * Both public and prefixed: it gets the two sets of routes.
 */
class RouteCachePublicPrefixedPostRepository extends RouteCachePrefixedPostRepository
{
    public static bool|array $public = true;

    public static string $uriKey = 'route-cache-public-prefixed-posts';
}

class RouteCacheFiltersRepository extends RouteCachePrefixedPostRepository
{
    public static string $uriKey = 'filters';
}
