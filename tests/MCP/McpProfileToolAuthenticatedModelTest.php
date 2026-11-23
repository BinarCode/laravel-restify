<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\UserProfile;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithAdminGuard;
use Binaryk\LaravelRestify\Tests\Fixtures\Admin\Admin;
use Binaryk\LaravelRestify\Tests\Fixtures\Admin\StaffAdmin;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as FoundationUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The profile tool of the users repository returns the authenticated account only when that account is a row of
 * the repository's model. An account of another table has no row there, whatever its id is.
 */
class McpProfileToolAuthenticatedModelTest extends IntegrationTestCase
{
    use InteractsWithAdminGuard;

    private const ENDPOINT = 'mcp-profile-authenticated-model';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        User::factory()->count(2)->create();

        $this->user = User::factory()->create(['name' => 'User Name', 'email' => 'user@example.com']);

        $this->mockPosts($this->user->getKey(), 2);

        $this->useUsersRepository(McpProfileAccountUserRepository::class);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    /**
     * @param  class-string<Admin|StaffAdmin>  $model
     */
    #[Test]
    #[DataProvider('accounts_stored_outside_the_users_table')]
    public function an_account_sharing_its_id_with_a_user_is_not_returned_that_user(string $model): void
    {
        $this->actingAs($this->createAdmin($this->user->getKey(), model: $model), Admin::GUARD);

        $this->assertNoRow($this->callProfileTool());
        $this->assertNoRow($this->callProfileTool(['include' => 'posts']));
    }

    /**
     * @return array<string, array{class-string<Admin|StaffAdmin>}>
     */
    public static function accounts_stored_outside_the_users_table(): array
    {
        return [
            'a model of its own' => [Admin::class],
            'a model extending the users model' => [StaffAdmin::class],
        ];
    }

    #[Test]
    public function a_user_that_is_not_an_eloquent_model_is_not_returned_the_user_sharing_its_id(): void
    {
        $this->actingAs(new GenericUser(['id' => $this->user->getKey(), 'name' => 'Generic Name']));

        $this->assertNoRow($this->callProfileTool());
    }

    #[Test]
    public function a_repository_names_the_row_of_a_user_that_is_not_stored_as_its_model(): void
    {
        $this->useUsersRepository(McpProfileTokenUserRepository::class);

        $this->actingAs(new GenericUser(['id' => $this->user->getKey(), 'name' => 'Generic Name']));

        $this->assertOnlyTheUser($this->callProfileTool());
    }

    #[Test]
    public function the_wrapper_execute_operation_does_not_return_the_user_sharing_the_id_either(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);

        $this->actingAs($this->createAdmin($this->user->getKey()), Admin::GUARD);

        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'users',
                    'operation_type' => 'profile',
                    'parameters' => ['include' => 'posts'],
                ],
            ],
        ])->assertOk();

        $this->assertFalse($response->json('result.isError'));
        $this->assertNoRow($response->json('result.structuredContent'));
    }

    #[Test]
    public function a_user_is_still_returned_its_own_row_next_to_an_admin_sharing_its_id(): void
    {
        $this->createAdmin($this->user->getKey());

        $this->actingAs($this->user);

        $result = $this->callProfileTool(['include' => 'posts']);

        $this->assertOnlyTheUser($result);
        $this->assertSame('user@example.com', $result['data'][0]['attributes']['email']);
        $this->assertCount(2, $result['data'][0]['relationships']['posts']);
    }

    /**
     * @param  class-string<Authenticatable>  $model
     */
    #[Test]
    #[DataProvider('other_classes_of_the_users_table')]
    public function a_user_read_through_another_class_of_the_users_table_is_returned_its_own_row(string $model): void
    {
        $this->actingAs($model::query()->findOrFail($this->user->getKey()));

        $this->assertOnlyTheUser($this->callProfileTool());
    }

    /**
     * @return array<string, array{class-string<Authenticatable>}>
     */
    public static function other_classes_of_the_users_table(): array
    {
        return [
            'a class that does not extend the users model' => [McpProfileUsersTableAccount::class],
            'a users model whose route key is another column' => [McpProfileRouteKeyUser::class],
            'a users model signed in by another column' => [McpProfileEmailIdentifiedUser::class],
        ];
    }

    /**
     * The only repository keyed `users`, so the tool and the request it builds resolve the same one.
     *
     * @param  class-string<Repository>  $repository
     */
    private function useUsersRepository(string $repository): void
    {
        Restify::$repositories = [];

        Restify::repositories([$repository]);

        $this->assertSame($repository, Restify::repositoryClassForKey('users'));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callProfileTool(array $arguments = []): array
    {
        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'users-profile-tool',
                'arguments' => (object) $arguments,
            ],
        ])->assertOk();

        $this->assertFalse($response->json('result.isError'), (string) $response->getContent());

        return $response->json('result.structuredContent');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function assertNoRow(array $result): void
    {
        $this->assertSame([], $result['data'], 'The profile tool returned a row of the users table to an account that is not stored there.');
        $this->assertSame(0, $result['meta']['total']);
        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertArrayHasKey('links', $result);
        $this->assertStringNotContainsString('user@example.com', (string) json_encode($result));
        $this->assertSame(3, DB::table('users')->count());
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function assertOnlyTheUser(array $result): void
    {
        $this->assertSame([(string) $this->user->getKey()], array_column($result['data'], 'id'));
        $this->assertSame(1, $result['meta']['total']);
    }
}

class McpProfileAccountUserRepository extends Repository
{
    use HasMcpTools;
    use UserProfile;

    public static string $model = User::class;

    public static string $uriKey = 'users';

    public static array $related = ['posts'];

    public static function canUseForProfile(Request $request): bool
    {
        return true;
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('name'),
            Field::make('email'),
        ];
    }
}

/**
 * Its users may authenticate as a generic user, whose id is the key of their row.
 */
class McpProfileTokenUserRepository extends McpProfileAccountUserRepository
{
    public static function profileKey(?Authenticatable $user): mixed
    {
        return $user instanceof GenericUser ? $user->getAuthIdentifier() : parent::profileKey($user);
    }
}

/**
 * Stored in the users table without extending the model of the users repository.
 */
class McpProfileUsersTableAccount extends FoundationUser
{
    protected $table = 'users';
}

class McpProfileRouteKeyUser extends User
{
    protected $table = 'users';

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}

class McpProfileEmailIdentifiedUser extends User
{
    protected $table = 'users';

    public function getAuthIdentifierName(): string
    {
        return 'email';
    }
}
