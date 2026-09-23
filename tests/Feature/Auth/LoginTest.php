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
    #[TestWith([null, null], 'no ttl means no expiry')]
    public function the_login_token_ttl_is_computed_in_seconds(?string $tokenTtl, ?int $expectedExpiresIn): void
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

    #[Test]
    #[TestWith([['password' => 'secret']], 'missing email')]
    #[TestWith([['email' => 'not-an-email', 'password' => 'secret']], 'invalid email')]
    #[TestWith([['email' => 'user@restify.test']], 'missing password')]
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

        $tempFile = tempnam(sys_get_temp_dir(), 'restify-stub-');
        file_put_contents($tempFile, $source);

        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $e) {
            $this->fail("LoginController.stub does not compile as PHP: {$e->getMessage()}");
        } finally {
            unlink($tempFile);
        }

        $this->assertStringNotContainsString('abort(401,', $contents);
        $this->assertStringContainsString('JsonResponse::HTTP_UNAUTHORIZED', $contents);
        $this->assertStringNotContainsString('whereEmail(', $contents);
        $this->assertStringContainsString("->where('email',", $contents);
        $this->assertStringNotContainsString('$user->password)', $contents);
        $this->assertStringContainsString('getAuthPassword()', $contents);

        $this->addToAssertionCount(1);
    }

    private function loginStubContents(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Commands/stubs/Auth/LoginController.stub');
    }
}
