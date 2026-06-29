<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Models\SocialAccount;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialAuthTest extends IntegrationTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            SocialiteServiceProvider::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.auth.social.providers', [
            'github' => ['scopes' => ['read:user']],
        ]);

        Route::restifySocialAuth();
    }

    protected function tearDown(): void
    {
        Restify::$socialUserResolver = null;

        parent::tearDown();
    }

    protected function fakeSocialiteUser(array $overrides = []): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->id = $overrides['id'] ?? '123';
        $user->nickname = $overrides['nickname'] ?? 'octocat';
        $user->name = $overrides['name'] ?? 'The Octocat';
        $user->email = $overrides['email'] ?? 'octo@github.com';
        $user->avatar = $overrides['avatar'] ?? 'https://avatars.githubusercontent.com/octocat';
        $user->token = 'gho_access';
        $user->refreshToken = 'gho_refresh';
        $user->expiresIn = 3600;

        return $user;
    }

    public function test_redirect_endpoint_returns_provider_url(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(
            new RedirectResponse('https://github.com/login/oauth/authorize?client_id=test')
        );

        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

        $this->getJson('auth/social/github/redirect')
            ->assertOk()
            ->assertJson([
                'provider' => 'github',
                'url' => 'https://github.com/login/oauth/authorize?client_id=test',
            ]);
    }

    public function test_callback_creates_user_links_account_and_returns_token(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($this->fakeSocialiteUser());
        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

        $response = $this->getJson('auth/social/github/callback?code=abc')
            ->assertOk()
            ->assertJsonFragment(['token' => 'token'])
            ->assertJsonFragment(['provider' => 'github']);

        $this->assertDatabaseHas('users', ['email' => 'octo@github.com']);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'github',
            'provider_id' => '123',
            'email' => 'octo@github.com',
        ]);

        // Tokens are persisted but hidden from serialization.
        $account = SocialAccount::firstWhere('provider_id', '123');
        $this->assertSame('gho_access', $account->access_token);
        $this->assertNotNull($account->user_id);
    }

    public function test_callback_links_to_existing_user_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'octo@github.com']);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($this->fakeSocialiteUser());
        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

        $this->getJson('auth/social/github/callback?code=abc')->assertOk();

        // No duplicate user created; the account links to the existing one.
        $this->assertSame(1, User::where('email', 'octo@github.com')->count());
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'github',
            'provider_id' => '123',
            'user_id' => $existing->getKey(),
        ]);
    }

    public function test_resolve_social_user_can_be_overridden(): void
    {
        $custom = User::factory()->create(['email' => 'override@example.com']);

        Restify::resolveSocialUserUsing(fn () => $custom);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($this->fakeSocialiteUser());
        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

        $this->getJson('auth/social/github/callback?code=abc')->assertOk();

        // The default resolver never ran, so no account row was created.
        $this->assertDatabaseCount('social_accounts', 0);
        $this->assertSame(0, User::where('email', 'octo@github.com')->count());
    }
}
