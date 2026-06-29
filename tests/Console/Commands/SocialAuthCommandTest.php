<?php

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Commands\SocialAuthCommand;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class SocialAuthCommandTest extends IntegrationTestCase
{
    public function test_env_keys_use_upper_snake_prefix(): void
    {
        $this->assertSame(
            ['GITHUB_CLIENT_ID', 'GITHUB_CLIENT_SECRET', 'GITHUB_REDIRECT_URI'],
            SocialAuthCommand::envKeysFor('github')
        );

        // Hyphenated driver names normalize to underscores.
        $this->assertSame('LINKEDIN_OPENID', SocialAuthCommand::envPrefix('linkedin-openid'));
    }

    public function test_appends_only_missing_env_keys(): void
    {
        $env = "APP_NAME=Restify\nGITHUB_CLIENT_ID=existing\n";

        $result = SocialAuthCommand::appendMissingEnv($env, ['github', 'google']);

        // github already declared -> not duplicated; google added.
        $this->assertStringContainsString('GITHUB_CLIENT_ID=existing', $result);
        $this->assertSame(1, substr_count($result, 'GITHUB_CLIENT_ID='));
        $this->assertStringContainsString('GOOGLE_CLIENT_ID=', $result);
        $this->assertStringContainsString('GOOGLE_REDIRECT_URI=${APP_URL}/api/auth/social/google/callback', $result);
    }

    public function test_appending_env_is_idempotent(): void
    {
        $env = "APP_NAME=Restify\n";

        $once = SocialAuthCommand::appendMissingEnv($env, ['github']);
        $this->assertNotNull($once);

        // Running again over the already-updated content changes nothing.
        $this->assertNull(SocialAuthCommand::appendMissingEnv($once, ['github']));
    }

    public function test_injects_provider_block_before_closing_bracket(): void
    {
        $services = <<<'PHP'
        <?php

        return [
            'mailgun' => [
                'domain' => env('MAILGUN_DOMAIN'),
            ],
        ];
        PHP;

        $result = SocialAuthCommand::injectServices($services, ['github']);

        $this->assertNotNull($result);
        $this->assertStringContainsString("'github' => [", $result);
        $this->assertStringContainsString("'client_id' => env('GITHUB_CLIENT_ID')", $result);

        // The github block sits inside the returned array (before the final "];").
        $this->assertLessThan(strrpos($result, '];'), strpos($result, "'github' =>"));

        // Still valid PHP that returns the expected array.
        $array = eval(str_replace('<?php', '', $result));
        $this->assertArrayHasKey('github', $array);
        $this->assertArrayHasKey('mailgun', $array);
    }

    public function test_injecting_services_is_idempotent(): void
    {
        $services = "<?php\n\nreturn [\n];\n";

        $once = SocialAuthCommand::injectServices($services, ['github', 'atlassian']);
        $this->assertNotNull($once);

        $this->assertNull(SocialAuthCommand::injectServices($once, ['github', 'atlassian']));
    }
}
