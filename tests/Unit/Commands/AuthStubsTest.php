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
    public function the_reset_password_stub_types_its_http_status_and_uses_force_fill(): void
    {
        $contents = $this->stubContents('ResetPasswordController.stub');

        $this->assertStringNotContainsString('abort(400,', $contents);
        $this->assertStringContainsString('JsonResponse::HTTP_BAD_REQUEST', $contents);
        $this->assertStringNotContainsString('$user->password =', $contents);
        $this->assertStringContainsString('forceFill', $contents);
    }

    private function stubContents(string $stub): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Commands/stubs/Auth/'.$stub);
    }
}
