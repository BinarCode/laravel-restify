<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ResetPasswordTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::restifyAuth('auth', ['resetPassword']);
    }

    #[Test]
    public function a_valid_token_resets_the_password(): void
    {
        $user = UserFactory::one(['email' => 'known@example.com']);
        $token = Password::createToken($user);

        $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertExactJson(['message' => 'Your password has been successfully reset.']);

        $fresh = User::query()->findOrFail($user->id);

        $this->assertTrue(Hash::check('new-password', $fresh->password));
    }

    #[Test]
    public function an_unknown_email_receives_the_identical_response_as_an_invalid_token_and_nothing_changes(): void
    {
        $user = UserFactory::one(['email' => 'known@example.com', 'password' => $originalPassword = Hash::make('original-password')]);
        $token = Password::createToken($user);

        $invalidTokenResponse = $this->postJson('auth/resetPassword', [
            'email' => $user->email,
            'token' => 'not-the-right-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $unknownEmailResponse = $this->postJson('auth/resetPassword', [
            'email' => 'unknown@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $invalidTokenResponse->assertStatus($unknownEmailResponse->status());
        $this->assertSame($invalidTokenResponse->json('message'), $unknownEmailResponse->json('message'));

        $fresh = User::query()->findOrFail($user->id);

        $this->assertSame($originalPassword, $fresh->password);
    }

    #[Test]
    #[TestWith([[]], 'all fields missing')]
    #[TestWith([['email' => 'not-an-email', 'token' => 'a-token', 'password' => 'secret', 'password_confirmation' => 'secret']], 'email malformed')]
    #[TestWith([['email' => 'known@example.com', 'token' => 'a-token', 'password' => 'secret', 'password_confirmation' => 'different']], 'password confirmation mismatch')]
    public function invalid_input_is_rejected(array $payload): void
    {
        $this->postJson('auth/resetPassword', $payload)->assertStatus(422);
    }
}
