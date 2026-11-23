<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\UserProfile;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithAdminGuard;
use Binaryk\LaravelRestify\Tests\Fixtures\Admin\Admin;
use Binaryk\LaravelRestify\Tests\Fixtures\Admin\StaffAdmin;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SecondaryConnectionUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as FoundationUser;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\SanctumServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The profile endpoints serve the authenticated account. The users repository is only used for it when that account
 * is a row of the repository's model: an id alone does not say which table it comes from, so an admin signed in
 * through another guard is never the user who happens to share its id.
 */
class ProfileAuthenticatedModelTest extends IntegrationTestCase
{
    use InteractsWithAdminGuard;

    private const USER_PASSWORD = 'user-password';

    private const ADMIN_PASSWORD = 'admin-password';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Cheap hashes: these tests hash and check a password on almost every request.
        config(['hashing.bcrypt.rounds' => 4]);

        $this->user = User::factory()->create([
            'name' => 'User Name',
            'email' => 'user@example.com',
            'password' => Hash::make(self::USER_PASSWORD),
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            SanctumServiceProvider::class,
        ]);
    }

    /**
     * @param  class-string<Admin|StaffAdmin>  $model
     */
    #[Test]
    #[DataProvider('accounts_stored_outside_the_users_table')]
    public function an_account_sharing_its_id_with_a_user_is_shown_its_own_profile(string $model): void
    {
        $this->actingAsAdminSharingTheUsersId($model);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertDontSee('user@example.com')
            ->assertDontSee('User Name')
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonPath('data.name', 'Admin Name')
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.attributes');
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
    public function an_account_without_a_user_under_its_id_is_shown_its_own_profile(): void
    {
        $this->actingAs($this->createAdmin($this->user->getKey() + 100), Admin::GUARD);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    #[Test]
    public function an_admin_holding_a_sanctum_token_is_shown_its_own_profile_and_updates_it(): void
    {
        config(['restify.middleware' => [
            'api',
            'auth:sanctum',
            DispatchRestifyStartingEvent::class,
            AuthorizeRestify::class,
        ]]);

        UserRepository::$canUseForProfileUpdate = true;

        $admin = $this->createAdmin($this->user->getKey());

        $this->logout();

        $this->getJson(Restify::path('profile'))->assertUnauthorized();

        $this->withToken($admin->createToken('cli')->plainTextToken);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertDontSee('user@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'admin@example.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Changed Name');

        $this->assertUserIsUntouched();
        $this->assertSame('Changed Name', $this->adminColumn('name', $admin->getKey()));
    }

    /**
     * @param  class-string<Admin|StaffAdmin>  $model
     */
    #[Test]
    #[DataProvider('accounts_stored_outside_the_users_table')]
    public function the_repository_driven_update_writes_the_account_and_not_the_user_sharing_its_id(string $model): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $admin = $this->actingAsAdminSharingTheUsersId($model);

        // The email is the admin's own, so only the name changes. The users repository requires an email and
        // accepts this one, as no user has it.
        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'admin@example.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Changed Name')
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonMissingPath('data.attributes');

        $this->assertUserIsUntouched();
        $this->assertSame('Changed Name', $this->adminColumn('name', $admin->getKey()));
    }

    #[Test]
    public function the_repository_driven_update_does_not_change_the_credentials_of_the_user_sharing_the_id(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $admin = $this->actingAsAdminSharingTheUsersId();

        // The users repository would write both fields as sent. For another account they are credentials, which
        // only change together with the current password of that account.
        $this->putJson(Restify::path('profile'), ['email' => 'chosen@example.com', 'password' => 'chosen-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->putJson(Restify::path('profile'), [
            'email' => 'chosen@example.com',
            'password' => 'chosen-password',
            'password_confirmation' => 'chosen-password',
            'current_password' => self::USER_PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertUserIsUntouched();
        $this->assertSame('admin@example.com', $this->adminColumn('email', $admin->getKey()));
        $this->assertTrue(Hash::check(self::ADMIN_PASSWORD, $this->adminColumn('password', $admin->getKey())));

        $this->putJson(Restify::path('profile'), [
            'email' => 'chosen@example.com',
            'password' => 'chosen-password',
            'password_confirmation' => 'chosen-password',
            'current_password' => self::ADMIN_PASSWORD,
        ])->assertOk();

        $this->assertUserIsUntouched();
        $this->assertSame('chosen@example.com', $this->adminColumn('email', $admin->getKey()));
        $this->assertTrue(Hash::check('chosen-password', $this->adminColumn('password', $admin->getKey())));
    }

    #[Test]
    public function the_update_without_the_repository_writes_the_account_and_not_the_user_sharing_its_id(): void
    {
        $this->assertFalse(UserRepository::canUseForProfileUpdate(new Request));

        $admin = $this->actingAsAdminSharingTheUsersId();

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Changed Name')
            ->assertJsonPath('data.email', 'admin@example.com');

        $this->assertUserIsUntouched();
        $this->assertSame('Changed Name', $this->adminColumn('name', $admin->getKey()));
    }

    #[Test]
    public function an_avatar_upload_does_not_reach_the_user_sharing_the_id(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('canUseForProfileUpdate')
            ->andReturnTrue()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('avatar_size'),
                field('avatar_original'),

                Image::make('avatar')
                    ->rules('required')
                    ->disk('customDisk')
                    ->storeOriginalName('avatar_original')
                    ->storeSize('avatar_size')
                    ->storeAs('avatar.jpg'),
            ]);

        $admin = $this->actingAsAdminSharingTheUsersId();

        $this->postJson(Restify::path('profile'), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonPath('data.avatar', null);

        $this->assertUserIsUntouched();
        $this->assertNull($this->userColumn('avatar'));
        $this->assertNull($this->userColumn('avatar_original'));
        $this->assertNull($this->adminColumn('avatar', $admin->getKey()));

        Storage::disk('customDisk')->assertMissing('avatar.jpg');
    }

    #[Test]
    public function a_user_that_is_not_an_eloquent_model_is_not_the_user_sharing_its_id(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->actingAs(new GenericUser(['id' => $this->user->getKey(), 'name' => 'Generic Name']));

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertDontSee('user@example.com')
            ->assertJsonMissingPath('data.attributes')
            ->assertJsonMissingPath('data.email');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertUnauthorized();

        $this->assertUserIsUntouched();
    }

    #[Test]
    public function an_account_of_a_users_table_on_another_connection_is_not_the_user_sharing_its_id(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $connection = SecondaryConnectionUser::CONNECTION;

        config(["database.connections.{$connection}" => ['driver' => 'sqlite', 'database' => ':memory:']]);

        Schema::connection($connection)->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        DB::connection($connection)->table('users')->insert([
            'id' => $this->user->getKey(),
            'name' => 'Other Database Name',
            'email' => 'other-database@example.com',
            'password' => Hash::make('other-database-password'),
        ]);

        $this->actingAs(SecondaryConnectionUser::query()->findOrFail($this->user->getKey()));

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertDontSee('user@example.com')
            ->assertJsonPath('data.email', 'other-database@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'other-database@example.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Changed Name');

        $this->assertUserIsUntouched();
        $this->assertSame('Changed Name', DB::connection($connection)->table('users')->value('name'));
    }

    #[Test]
    public function a_repository_names_the_row_of_a_user_that_is_not_stored_as_its_model(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->useUsersRepository(ProfileTokenUserRepository::class);

        $this->actingAs(new GenericUser(['id' => $this->user->getKey(), 'name' => 'Generic Name']));

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.attributes.email', 'user@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Changed Name');

        $this->assertSame('Changed Name', $this->userColumn('name'));
        $this->assertSame('changed@example.com', $this->userColumn('email'));
    }

    #[Test]
    public function without_an_authenticated_user_there_is_no_profile(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->logout();

        $this->getJson(Restify::path('profile'))->assertNotFound();

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertUnauthorized();

        $this->assertUserIsUntouched();
    }

    #[Test]
    public function a_user_is_still_served_by_the_users_repository_next_to_an_admin_sharing_its_id(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $admin = $this->createAdmin($this->user->getKey());

        $this->actingAs($this->user);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.attributes.name', 'User Name')
            ->assertJsonPath('data.attributes.email', 'user@example.com')
            ->assertDontSee('admin@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Changed Name')
            ->assertJsonPath('data.attributes.email', 'changed@example.com');

        $this->assertSame('Changed Name', $this->userColumn('name'));
        $this->assertSame('changed@example.com', $this->userColumn('email'));
        $this->assertSame('Admin Name', $this->adminColumn('name', $admin->getKey()));
        $this->assertSame('admin@example.com', $this->adminColumn('email', $admin->getKey()));
    }

    #[Test]
    public function a_user_read_through_another_class_of_the_users_table_is_served_by_the_users_repository(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        // Stored in the users table like the repository's model, without extending it.
        $this->actingAs(ProfileUsersTableAccount::query()->findOrFail($this->user->getKey()));

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.attributes.email', 'user@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Changed Name');

        $this->assertSame('Changed Name', $this->userColumn('name'));
        $this->assertSame('changed@example.com', $this->userColumn('email'));
    }

    #[Test]
    public function the_profile_is_matched_on_the_primary_key_when_the_route_key_is_another_column(): void
    {
        $this->useUsersRepository(ProfileRouteKeyUserRepository::class);

        $this->actingAs(ProfileRouteKeyUser::query()->findOrFail($this->user->getKey()));

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.attributes.email', 'user@example.com');

        // Another user whose route key reads like the primary key of the caller.
        $other = User::factory()->create(['name' => (string) $this->user->getKey(), 'email' => 'other@example.com']);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.attributes.email', 'user@example.com')
            ->assertDontSee('other@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name'])
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.attributes.name', 'Changed Name');

        $this->assertSame('Changed Name', $this->userColumn('name'));
        $this->assertSame((string) $this->user->getKey(), $this->userColumn('name', $other->getKey()));
    }

    #[Test]
    public function the_profile_is_matched_on_the_primary_key_when_the_user_signs_in_by_another_column(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $user = ProfileEmailIdentifiedUser::query()->findOrFail($this->user->getKey());

        $this->assertSame('user@example.com', $user->getAuthIdentifier());

        $this->actingAs($user);

        $this->getJson(Restify::path('profile'))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $this->user->getKey())
            ->assertJsonPath('data.attributes.email', 'user@example.com');

        $this->putJson(Restify::path('profile'), ['name' => 'Changed Name', 'email' => 'changed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Changed Name');

        $this->assertSame('Changed Name', $this->userColumn('name'));
        $this->assertSame('changed@example.com', $this->userColumn('email'));
    }

    /**
     * @template TAdmin of Admin|StaffAdmin
     *
     * @param  class-string<TAdmin>  $model
     * @return TAdmin
     */
    private function actingAsAdminSharingTheUsersId(string $model = Admin::class): Authenticatable
    {
        $admin = $this->createAdmin($this->user->getKey(), model: $model);

        $this->assertSame($this->user->getKey(), $admin->getKey());

        $this->actingAs($admin, Admin::GUARD);

        return $admin;
    }

    /**
     * @param  class-string<Repository>  $repository
     */
    private function useUsersRepository(string $repository): void
    {
        Restify::$repositories = [];

        Restify::repositories([$repository]);

        $this->assertSame($repository, Restify::repositoryClassForKey('users'));
    }

    private function userColumn(string $column, int|string|null $id = null): mixed
    {
        return DB::table('users')->where('id', $id ?? $this->user->getKey())->value($column);
    }

    private function assertUserIsUntouched(): void
    {
        $this->assertSame('User Name', $this->userColumn('name'));
        $this->assertSame('user@example.com', $this->userColumn('email'));
        $this->assertTrue(Hash::check(self::USER_PASSWORD, $this->userColumn('password')), 'The password of the user changed.');
    }
}

/**
 * Stored in the users table without extending the model of the users repository.
 */
class ProfileUsersTableAccount extends FoundationUser
{
    protected $table = 'users';
}

class ProfileRouteKeyUser extends User
{
    protected $table = 'users';

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}

class ProfileEmailIdentifiedUser extends User
{
    protected $table = 'users';

    public function getAuthIdentifierName(): string
    {
        return 'email';
    }
}

/**
 * Its users may authenticate as a generic user, whose id is the key of their row.
 */
class ProfileTokenUserRepository extends UserRepository
{
    public static string $uriKey = 'users';

    public static function profileKey(?Authenticatable $user): mixed
    {
        return $user instanceof GenericUser ? $user->getAuthIdentifier() : parent::profileKey($user);
    }
}

class ProfileRouteKeyUserRepository extends Repository
{
    use UserProfile;

    public static string $model = ProfileRouteKeyUser::class;

    public static string $uriKey = 'users';

    public static function canUseForProfile(Request $request): bool
    {
        return true;
    }

    public static function canUseForProfileUpdate(Request $request): bool
    {
        return true;
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email')->readonly(),
        ];
    }
}
