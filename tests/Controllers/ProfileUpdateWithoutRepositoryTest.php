<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserWithCustomPasswordColumn;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * PUT and POST /profile when the users repository is not used for the update (UserProfile, the fixture default).
 */
class ProfileUpdateWithoutRepositoryTest extends IntegrationTestCase
{
    private const CURRENT_PASSWORD = 'current-password';

    private const NEW_PASSWORD = 'new-password-1';

    protected function setUp(): void
    {
        parent::setUp();

        // Cheap hashes: these tests hash and check a password on almost every request.
        config(['hashing.bcrypt.rounds' => 4]);

        Notification::fake();

        $this->authenticate(User::factory()->create([
            'name' => 'Current Name',
            'email' => 'current@example.com',
            'password' => Hash::make(self::CURRENT_PASSWORD),
            'email_verified_at' => '2026-01-01 00:00:00',
            'active' => false,
            'creator_id' => null,
        ]));
    }

    protected function tearDown(): void
    {
        Password::$defaultCallback = null;

        parent::tearDown();
    }

    #[Test]
    public function it_updates_a_listed_attribute_without_the_current_password(): void
    {
        $this->putJson(Restify::path('profile'), ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonMissingPath('data.password');

        $this->assertSame('New Name', $this->column('name'));
    }

    #[Test]
    public function it_updates_through_the_post_route_too(): void
    {
        $this->postJson(Restify::path('profile'), ['name' => 'New Name', 'active' => true])->assertOk();

        $this->assertSame('New Name', $this->column('name'));
        $this->assertFalse((bool) $this->column('active'));
    }

    #[Test]
    #[DataProvider('unlisted_fillable_columns')]
    public function a_fillable_column_that_is_not_listed_is_not_written(string $column, mixed $value): void
    {
        $this->assertTrue($this->authenticatedAs->isFillable($column), "The fixture must keep [{$column}] fillable.");

        $before = $this->column($column);

        $this->putJson(Restify::path('profile'), ['name' => 'New Name', $column => $value])->assertOk();

        $this->assertSame('New Name', $this->column('name'));
        $this->assertSame($before, $this->column($column));
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function unlisted_fillable_columns(): array
    {
        return [
            'a flag' => ['active', true],
            'the verification date' => ['email_verified_at', null],
            'a foreign key' => ['creator_id', 99],
            'a file path' => ['avatar', 'avatars/other.jpg'],
            'a timestamp' => ['created_at', '2001-01-01 00:00:00'],
        ];
    }

    #[Test]
    public function the_primary_key_is_not_written(): void
    {
        $id = $this->authenticatedAs->getKey();

        $this->putJson(Restify::path('profile'), ['id' => 9999, 'name' => 'New Name'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $id, 'name' => 'New Name']);
        $this->assertDatabaseMissing('users', ['id' => 9999]);
    }

    #[Test]
    #[DataProvider('invalid_names')]
    public function a_listed_attribute_is_validated(mixed $name): void
    {
        $this->putJson(Restify::path('profile'), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame('Current Name', $this->column('name'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalid_names(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'a list' => [['New Name']],
            'too long' => [str_repeat('a', 256)],
        ];
    }

    #[Test]
    public function the_password_does_not_change_without_the_current_password(): void
    {
        $this->putJson(Restify::path('profile'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
    }

    #[Test]
    #[DataProvider('wrong_current_passwords')]
    public function the_password_does_not_change_with_a_wrong_current_password(mixed $currentPassword): void
    {
        $this->putJson(Restify::path('profile'), [
            'name' => 'New Name',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            'current_password' => $currentPassword,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
        $this->assertSame('Current Name', $this->column('name'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function wrong_current_passwords(): array
    {
        return [
            'another password' => ['not-the-password'],
            'the new password' => [self::NEW_PASSWORD],
            'empty' => [''],
            'a list' => [[self::CURRENT_PASSWORD]],
        ];
    }

    #[Test]
    public function the_password_changes_with_the_current_password_and_is_stored_hashed(): void
    {
        $this->putJson(Restify::path('profile'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            'current_password' => self::CURRENT_PASSWORD,
        ])
            ->assertOk()
            ->assertJsonMissingPath('data.password');

        $stored = $this->column('password');

        $this->assertNotSame(self::NEW_PASSWORD, $stored);
        $this->assertTrue(Hash::isHashed($stored));
        $this->assertPasswordIs(self::NEW_PASSWORD);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('invalid_new_passwords')]
    public function the_new_password_is_validated(array $payload): void
    {
        $this->putJson(Restify::path('profile'), $payload + ['current_password' => self::CURRENT_PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalid_new_passwords(): array
    {
        return [
            'not confirmed' => [['password' => self::NEW_PASSWORD]],
            'confirmed with another value' => [['password' => self::NEW_PASSWORD, 'password_confirmation' => 'another-password-1']],
            'too short' => [['password' => 'short-1', 'password_confirmation' => 'short-1']],
            'null' => [['password' => null, 'password_confirmation' => null]],
            'a list' => [['password' => [self::NEW_PASSWORD], 'password_confirmation' => [self::NEW_PASSWORD]]],
        ];
    }

    #[Test]
    public function the_new_password_follows_the_password_defaults_of_the_application(): void
    {
        Password::defaults(fn (): Password => Password::min(20));

        $this->putJson(Restify::path('profile'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            'current_password' => self::CURRENT_PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
    }

    #[Test]
    public function the_email_does_not_change_without_the_current_password(): void
    {
        $this->putJson(Restify::path('profile'), ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->putJson(Restify::path('profile'), ['email' => 'new@example.com', 'current_password' => 'not-the-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertSame('current@example.com', $this->column('email'));
        $this->assertSame('Current Name', $this->column('name'));
        $this->assertNotNull($this->column('email_verified_at'));
    }

    #[Test]
    public function the_email_changes_with_the_current_password_and_has_to_be_verified_again(): void
    {
        Route::restifyAuth('auth', ['verifyEmail']);
        Route::getRoutes()->refreshNameLookups();

        $this->putJson(Restify::path('profile'), [
            'email' => 'New@Example.com',
            'email_verified_at' => '2026-02-02 00:00:00',
            'current_password' => self::CURRENT_PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.email_verified_at', null);

        $this->assertSame('new@example.com', $this->column('email'));
        $this->assertNull($this->column('email_verified_at'));
        $this->assertPasswordIs(self::CURRENT_PASSWORD);

        Notification::assertSentTo($this->authenticatedAs, VerifyEmail::class);
    }

    #[Test]
    public function the_verification_email_is_not_sent_without_the_restify_verify_route(): void
    {
        $this->putJson(Restify::path('profile'), [
            'email' => 'new@example.com',
            'current_password' => self::CURRENT_PASSWORD,
        ])->assertOk();

        $this->assertSame('new@example.com', $this->column('email'));
        $this->assertNull($this->column('email_verified_at'));

        Notification::assertNothingSent();
    }

    #[Test]
    #[DataProvider('current_email_spellings')]
    public function sending_the_current_email_back_needs_no_password_and_keeps_it_verified(string $email): void
    {
        $this->putJson(Restify::path('profile'), ['name' => 'New Name', 'email' => $email])->assertOk();

        $this->assertSame('New Name', $this->column('name'));
        $this->assertSame('current@example.com', $this->column('email'));
        $this->assertNotNull($this->column('email_verified_at'));

        Notification::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function current_email_spellings(): array
    {
        return [
            'as stored' => ['current@example.com'],
            'in another casing' => ['Current@Example.COM'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('invalid_emails')]
    public function the_new_email_is_validated(array $payload): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->putJson(Restify::path('profile'), $payload + ['current_password' => self::CURRENT_PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame('current@example.com', $this->column('email'));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalid_emails(): array
    {
        return [
            'taken' => [['email' => 'taken@example.com']],
            'taken, in another casing' => [['email' => 'Taken@Example.com']],
            'not an email' => [['email' => 'not-an-email']],
            'null' => [['email' => null]],
            'a list' => [['email' => ['new@example.com']]],
        ];
    }

    #[Test]
    public function the_repository_lists_more_attributes_by_assigning_the_rules(): void
    {
        UserRepository::$profileUpdateRules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ];

        $this->putJson(Restify::path('profile'), ['active' => 'yes'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('active');

        $this->putJson(Restify::path('profile'), ['active' => true, 'creator_id' => 99])->assertOk();

        $this->assertTrue((bool) $this->column('active'));
        $this->assertNull($this->column('creator_id'));
    }

    #[Test]
    public function the_repository_lists_its_attributes_by_overriding_the_method(): void
    {
        $this->useUsersRepository(ProfileRulesUserRepository::class);

        $this->putJson(Restify::path('profile'), [
            'name' => 'New Name',
            'creator_id' => 99,
            'active' => true,
            'deleted_at' => '2026-03-03 00:00:00',
        ])->assertOk();

        $this->assertSame(99, (int) $this->column('creator_id'));
        $this->assertSame('Current Name', $this->column('name'), 'An attribute the repository does not list is not written.');
        $this->assertFalse((bool) $this->column('active'));
        $this->assertNull($this->column('deleted_at'), 'A listed attribute the model does not have fillable is not written.');
    }

    #[Test]
    public function the_key_the_password_and_the_email_cannot_be_listed_as_plain_attributes(): void
    {
        UserRepository::$profileUpdateRules = [
            'id' => ['sometimes', 'integer'],
            'email' => ['sometimes', 'string'],
            'password' => ['sometimes', 'string'],
            'remember_token' => ['sometimes', 'string'],
        ];

        $id = $this->authenticatedAs->getKey();
        $rememberToken = $this->column('remember_token');

        $this->putJson(Restify::path('profile'), ['id' => 9999, 'remember_token' => 'chosen-by-the-caller'])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => 9999]);
        $this->assertSame($rememberToken, DB::table('users')->where('id', $id)->value('remember_token'));

        $this->putJson(Restify::path('profile'), ['password' => self::NEW_PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'current_password']);

        $this->putJson(Restify::path('profile'), ['email' => 'new@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
        $this->assertSame('current@example.com', $this->column('email'));
    }

    #[Test]
    public function a_repository_without_the_trait_gets_the_default_attributes(): void
    {
        $this->useUsersRepository(ProfileWithoutTraitUserRepository::class);

        $this->putJson(Restify::path('profile'), ['name' => 'New Name', 'active' => true])->assertOk();

        $this->assertSame('New Name', $this->column('name'));
        $this->assertFalse((bool) $this->column('active'));

        $this->putJson(Restify::path('profile'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertUnprocessable();

        $this->assertPasswordIs(self::CURRENT_PASSWORD);
    }

    #[Test]
    public function an_unguarded_model_does_not_make_every_column_writable_and_its_password_column_is_only_written_hashed(): void
    {
        $user = UserWithCustomPasswordColumn::query()->create([
            'name' => Hash::make(self::CURRENT_PASSWORD),
            'email' => 'unguarded@example.com',
            'password' => 'not-the-auth-column',
            'active' => false,
        ]);

        $this->actingAs($user);

        UserRepository::$profileUpdateRules = [
            'name' => ['sometimes', 'string'],
            'Name' => ['sometimes', 'string'],
        ];

        // `name` is this model's password column, so it is not profile data, however the repository spells it.
        $this->putJson(Restify::path('profile'), [
            'name' => 'plain-text',
            'Name' => 'plain-text',
            'active' => true,
            'creator_id' => 99,
        ])->assertOk();

        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $this->column('name', $user->getKey())));
        $this->assertFalse((bool) $this->column('active', $user->getKey()));
        $this->assertNull($this->column('creator_id', $user->getKey()));

        $this->putJson(Restify::path('profile'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
            'current_password' => self::CURRENT_PASSWORD,
        ])->assertOk();

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $this->column('name', $user->getKey())));
        $this->assertSame('not-the-auth-column', $this->column('password', $user->getKey()));
    }

    #[Test]
    public function a_user_that_is_not_an_eloquent_model_is_refused(): void
    {
        $this->actingAs(new GenericUser(['id' => $this->authenticatedAs->getKey(), 'name' => 'Generic']));

        $this->putJson(Restify::path('profile'), ['name' => 'New Name'])->assertUnauthorized();

        $this->assertSame('Current Name', $this->column('name'));
    }

    #[Test]
    public function the_repository_driven_update_is_unchanged(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->putJson(Restify::path('profile'), ['email' => 'New@Example.com', 'name' => 'New Name', 'active' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.email', 'New@Example.com')
            ->assertJsonPath('data.attributes.name', 'New Name');

        $this->assertSame('New@Example.com', $this->column('email'));
        $this->assertFalse((bool) $this->column('active'));
        $this->assertNotNull($this->column('email_verified_at'));
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

    private function column(string $column, int|string|null $id = null): mixed
    {
        return DB::table('users')->where('id', $id ?? $this->authenticatedAs->getKey())->value($column);
    }

    private function assertPasswordIs(string $password): void
    {
        $this->assertTrue(Hash::check($password, $this->column('password')), 'The stored password is not the expected one.');
    }
}

class ProfileRulesUserRepository extends UserRepository
{
    public static string $uriKey = 'users';

    public static function profileUpdateRules(Request $request): array
    {
        return [
            'creator_id' => ['sometimes', 'nullable', 'integer'],
            'deleted_at' => ['sometimes', 'date'],
        ];
    }
}

class ProfileWithoutTraitUserRepository extends Repository
{
    public static string $model = User::class;

    public static string $uriKey = 'users';

    public static function canUseForProfileUpdate(Request $request): bool
    {
        return false;
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}
