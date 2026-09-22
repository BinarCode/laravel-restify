<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AuthStubsTest extends IntegrationTestCase
{
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

    private function stubContents(string $stub): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Commands/stubs/Auth/'.$stub);
    }
}
