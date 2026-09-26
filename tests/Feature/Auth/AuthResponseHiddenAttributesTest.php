<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\HiddenAttributesUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AuthResponseHiddenAttributesTest extends IntegrationTestCase
{
    /** @var list<class-string<Repository>> */
    private array $registeredRepositories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registeredRepositories = Restify::$repositories;
        Restify::$repositories = [HiddenAttributesUserRepository::class, ...Restify::$repositories];

        Route::restifyAuth('auth', ['register', 'login', 'verifyEmail']);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        Restify::$repositories = $this->registeredRepositories;

        parent::tearDown();
    }

    #[Test]
    public function the_login_response_keeps_its_shape_through_the_registered_user_repository(): void
    {
        $this->assertSame(HiddenAttributesUserRepository::class, Restify::repositoryForModel(User::class));

        $this->login()
            ->assertOk()
            ->assertExactJsonStructure([
                'id',
                'type',
                'attributes' => ['name', 'email'],
                'meta' => ['authorizedToShow', 'authorizedToStore', 'authorizedToUpdate', 'authorizedToDelete', 'token', 'expires_in'],
            ])
            ->assertJsonPath('type', 'users')
            ->assertJsonPath('attributes.email', 'jane@example.com')
            ->assertJsonPath('meta.token', 'token')
            ->assertJsonPath('meta.expires_in', null);
    }

    #[Test]
    #[TestWith(['password'], 'the password hash')]
    #[TestWith(['rememberToken'], 'a hidden attribute exposed under a field label')]
    public function the_login_response_omits_hidden_model_attributes(string $attribute): void
    {
        $this->login()
            ->assertOk()
            ->assertJsonPath('attributes.name', 'Jane Doe')
            ->assertJsonMissingPath("attributes.{$attribute}");
    }

    #[Test]
    #[TestWith(['password'], 'the password hash')]
    #[TestWith(['rememberToken'], 'a hidden attribute exposed under a field label')]
    public function the_register_response_omits_hidden_model_attributes(string $attribute): void
    {
        $this->postJson('auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret1',
            'password_confirmation' => 'secret1',
        ])
            ->assertOk()
            ->assertJsonPath('attributes.name', 'Jane Doe')
            ->assertJsonPath('meta.token', 'token')
            ->assertJsonMissingPath("attributes.{$attribute}");
    }

    #[Test]
    #[TestWith(['password'], 'the password hash')]
    #[TestWith(['rememberToken'], 'a hidden attribute exposed under a field label')]
    public function the_verify_response_omits_hidden_model_attributes(string $attribute): void
    {
        config(['restify.auth.user_verify_url' => null]);

        $user = $this->createUser();

        $verifyUrl = URL::temporarySignedRoute('restify.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->postJson($verifyUrl)
            ->assertOk()
            ->assertJsonPath('attributes.name', 'Jane Doe')
            ->assertJsonPath('meta.message', 'Email verified successfully.')
            ->assertJsonMissingPath("attributes.{$attribute}");
    }

    #[Test]
    #[TestWith(['LoginController.stub'], 'login')]
    #[TestWith(['RegisterController.stub'], 'register')]
    #[TestWith(['VerifyController.stub'], 'verify')]
    public function the_published_auth_stub_omits_hidden_model_attributes(string $stub): void
    {
        $this->assertStringContainsString(
            'rest($user)->withoutHiddenAttributes()->indexMeta(',
            (string) file_get_contents(dirname(__DIR__, 3)."/src/Commands/stubs/Auth/{$stub}"),
        );
    }

    private function login(): TestResponse
    {
        $this->createUser();

        return $this->postJson('auth/login', [
            'email' => 'jane@example.com',
            'password' => 'correct-password',
        ]);
    }

    private function createUser(): User
    {
        return UserFactory::one([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => null,
        ]);
    }
}
