<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserWithCustomPasswordColumn;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use ParseError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class LoginTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::restifyAuth('auth', ['login']);
    }

    #[Test]
    public function valid_credentials_return_ok_with_a_token(): void
    {
        UserFactory::one([
            'email' => 'user@restify.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('auth/login', [
            'email' => 'user@restify.test',
            'password' => 'correct-password',
        ])
            ->assertOk()
            ->assertJsonPath('meta.token', 'token')
            ->assertJsonPath('meta.expires_in', null);
    }

    #[Test]
    #[TestWith(['0.5', 30], 'a sub-minute ttl is rounded to seconds instead of truncated to zero')]
    #[TestWith(['1.5', 90], 'a fractional ttl is rounded to seconds instead of truncated')]
    #[TestWith(['60', 3600], 'a whole-minute ttl from env converts to seconds')]
    #[TestWith([null, null], 'no ttl means no expiry')]
    #[TestWith([0, null], 'a zero int ttl means no expiry')]
    #[TestWith(['0', null], 'a zero string ttl means no expiry')]
    #[TestWith([-5, null], 'a negative ttl means no expiry')]
    #[TestWith(['abc', null], 'a non-numeric ttl means no expiry')]
    #[TestWith([60, 3600], 'a whole-minute int ttl converts to seconds')]
    public function the_login_token_ttl_is_computed_in_seconds(int|string|null $tokenTtl, ?int $expectedExpiresIn): void
    {
        config(['restify.auth.token_ttl' => $tokenTtl]);

        UserFactory::one([
            'email' => 'user@restify.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('auth/login', [
            'email' => 'user@restify.test',
            'password' => 'correct-password',
        ])
            ->assertOk()
            ->assertJsonPath('meta.expires_in', $expectedExpiresIn);
    }

    #[Test]
    public function a_numeric_json_password_is_accepted(): void
    {
        UserFactory::one([
            'email' => 'user@restify.test',
            'password' => Hash::make('123456'),
        ]);

        $this->postJson('auth/login', [
            'email' => 'user@restify.test',
            'password' => 123456,
        ])
            ->assertOk()
            ->assertJsonPath('meta.token', 'token');
    }

    #[Test]
    public function wrong_password_is_rejected_with_the_documented_status(): void
    {
        UserFactory::one([
            'email' => 'user@restify.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('auth/login', [
            'email' => 'user@restify.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(JsonResponse::HTTP_UNAUTHORIZED)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    #[Test]
    public function unknown_email_gets_the_same_response_as_a_wrong_password_so_it_does_not_leak_account_existence(): void
    {
        UserFactory::one([
            'email' => 'user@restify.test',
            'password' => Hash::make('correct-password'),
        ]);

        $wrongPassword = $this->postJson('auth/login', [
            'email' => 'user@restify.test',
            'password' => 'wrong-password',
        ]);

        $unknownEmail = $this->postJson('auth/login', [
            'email' => 'nobody@restify.test',
            'password' => 'wrong-password',
        ]);

        $unknownEmail
            ->assertStatus($wrongPassword->getStatusCode())
            ->assertExactJson($wrongPassword->json());
    }

    #[Test]
    public function it_checks_the_password_against_the_configured_auth_password_column(): void
    {
        config(['restify.auth.user_model' => UserWithCustomPasswordColumn::class]);

        UserWithCustomPasswordColumn::query()->create([
            'name' => Hash::make('correct-password'),
            'email' => 'custom-column@restify.test',
            'password' => Hash::make('a-different-hash'),
        ]);

        $this->postJson('auth/login', [
            'email' => 'custom-column@restify.test',
            'password' => 'correct-password',
        ])->assertOk();
    }

    /**
     * @param  array<string, string|list<string>>  $payload
     */
    #[Test]
    #[TestWith([['password' => 'secret']], 'missing email')]
    #[TestWith([['email' => 'not-an-email', 'password' => 'secret']], 'invalid email')]
    #[TestWith([['email' => 'user@restify.test']], 'missing password')]
    #[TestWith([['email' => 'user@restify.test', 'password' => ['x']]], 'array password')]
    public function invalid_input_is_rejected_with_a_validation_error(array $payload): void
    {
        $this->postJson('auth/login', $payload)
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function the_published_login_stub_is_syntactically_valid_and_carries_the_same_fixes(): void
    {
        $contents = $this->loginStubContents();

        $source = str_replace('{{namespace}}', 'App\\Http\\Controllers\\Restify\\Auth', $contents);

        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $e) {
            $this->fail("LoginController.stub does not compile as PHP: {$e->getMessage()}");
        }

        $this->assertStringNotContainsString('abort(401,', $contents);
        $this->assertStringContainsString('JsonResponse::HTTP_UNAUTHORIZED', $contents);
        $this->assertStringNotContainsString('whereEmail(', $contents);
        $this->assertStringContainsString("->where('email',", $contents);
        $this->assertStringNotContainsString('$user->password)', $contents);
        $this->assertStringContainsString('getAuthPassword()', $contents);
        $this->assertStringContainsString('self::scalarPasswordRule()', $contents);
    }

    private function loginStubContents(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Commands/stubs/Auth/LoginController.stub');
    }
}
