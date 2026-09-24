<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use ParseError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AuthStubsTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['ForgotPasswordController.stub'])]
    #[TestWith(['ResetPasswordController.stub'])]
    public function the_published_auth_stub_is_syntactically_valid_php(string $stub): void
    {
        $source = str_replace('{{namespace}}', 'App\\Http\\Controllers\\Restify\\Auth', $this->stubContents($stub));

        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $e) {
            $this->fail("Stub {$stub} does not compile as PHP: {$e->getMessage()}");
        }

        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestWith(['ForgotPasswordController.stub'])]
    #[TestWith(['ResetPasswordController.stub'])]
    public function the_published_auth_stub_does_not_leak_account_existence(string $stub): void
    {
        $contents = $this->stubContents($stub);

        $this->assertStringNotContainsString('firstOrFail()', $contents);
        $this->assertStringContainsString('->first()', $contents);
    }

    #[Test]
    public function the_forgot_password_stub_types_the_user_as_can_reset_password(): void
    {
        $contents = $this->stubContents('ForgotPasswordController.stub');

        $this->assertStringNotContainsString('App\\Models\\User', $contents);
        $this->assertStringContainsString('use Illuminate\\Contracts\\Auth\\CanResetPassword;', $contents);
        $this->assertStringContainsString('sendResetLinkTo(CanResetPassword $user, Request $request): void', $contents);
    }

    #[Test]
    public function the_reset_password_stub_types_its_http_status_and_uses_force_fill(): void
    {
        $contents = $this->stubContents('ResetPasswordController.stub');

        $this->assertStringNotContainsString('abort(400,', $contents);
        $this->assertStringContainsString('JsonResponse::HTTP_BAD_REQUEST', $contents);
        $this->assertStringNotContainsString('$user->password =', $contents);
        $this->assertStringContainsString('forceFill', $contents);
    }

    #[Test]
    public function the_forgot_password_stub_restricts_the_client_supplied_url_host(): void
    {
        $contents = $this->stubContents('ForgotPasswordController.stub');

        $this->assertStringContainsString('AllowedResetUrlHost', $contents);
    }

    #[Test]
    public function the_reset_password_stub_types_the_user_as_can_reset_password(): void
    {
        $contents = $this->stubContents('ResetPasswordController.stub');

        $this->assertStringNotContainsString('App\\Models\\User', $contents);
        $this->assertStringContainsString('use Illuminate\\Contracts\\Auth\\CanResetPassword;', $contents);
        $this->assertStringContainsString('/** @var CanResetPassword|null $user */', $contents);
    }

    #[Test]
    #[TestWith(['ForgotPasswordController.stub'])]
    #[TestWith(['ResetPasswordController.stub'])]
    public function the_published_auth_stub_computes_its_response_inside_a_timebox(string $stub): void
    {
        $contents = $this->stubContents($stub);

        $this->assertStringContainsString('use Illuminate\\Support\\Timebox;', $contents);
        $this->assertStringContainsString('app(Timebox::class)->call(', $contents);
        $this->assertStringContainsString("config('restify.auth.password_reset_timebox')", $contents);
    }

    private function stubContents(string $stub): string
    {
        $path = dirname(__DIR__, 3).'/src/Commands/stubs/Auth/'.$stub;

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
