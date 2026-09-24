<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;

class AuthRouteThrottleTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->withMiddleware(ThrottleRequests::class);

        $this->app['auth']->forgetGuards();
    }

    #[Test]
    #[TestWith(['register', 'auth/register'], 'register')]
    #[TestWith(['login', 'auth/login'], 'login')]
    #[TestWith(['verifyEmail', 'auth/verify/1/hash'], 'verifyEmail')]
    #[TestWith(['forgotPassword', 'auth/forgotPassword'], 'forgotPassword')]
    #[TestWith(['resetPassword', 'auth/resetPassword'], 'resetPassword')]
    public function a_guest_is_throttled_after_six_attempts_a_minute(string $action, string $uri): void
    {
        Route::restifyAuth('auth', [$action]);

        foreach (range(1, 6) as $attempt) {
            $this->assertNotSame(JsonResponse::HTTP_TOO_MANY_REQUESTS, $this->postJson($uri)->getStatusCode());
        }

        $this->postJson($uri)->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);
    }

    #[Test]
    #[TestWith(['registerRoute.stub', 'restify.register'], 'register')]
    #[TestWith(['loginRoute.stub', 'restify.login'], 'login')]
    #[TestWith(['verifyRoute.stub', 'restify.verify'], 'verifyEmail')]
    #[TestWith(['forgotPasswordRoute.stub', 'restify.forgotPassword'], 'forgotPassword')]
    #[TestWith(['resetPasswordRoute.stub', 'restify.resetPassword'], 'resetPassword')]
    public function the_route_published_by_restify_auth_is_throttled_by_its_named_limiter(string $stub, string $limiterName): void
    {
        $this->assertStringContainsString(
            "->middleware('throttle:{$limiterName}')",
            file_get_contents(__DIR__.'/../../../src/Commands/stubs/Routes/'.$stub),
        );
    }

    #[Test]
    public function six_logins_for_one_email_throttle_only_that_email_not_a_different_one_from_the_same_ip(): void
    {
        Route::restifyAuth('auth', ['login']);

        foreach (range(1, RestifyApplicationServiceProvider::AUTH_ATTEMPTS_PER_MINUTE) as $attempt) {
            $this->assertNotSame(
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
                $this->postJson('auth/login', ['email' => 'a@x.com'])->getStatusCode()
            );
        }

        $this->postJson('auth/login', ['email' => 'a@x.com'])
            ->assertTooManyRequests();

        $this->assertNotSame(
            JsonResponse::HTTP_TOO_MANY_REQUESTS,
            $this->postJson('auth/login', ['email' => 'b@x.com'])->getStatusCode()
        );
    }

    #[Test]
    public function the_thirty_first_login_from_one_ip_across_different_emails_is_throttled(): void
    {
        Route::restifyAuth('auth', ['login']);

        foreach (range(1, RestifyApplicationServiceProvider::LOGIN_ATTEMPTS_PER_IP_PER_MINUTE) as $i) {
            $this->assertNotSame(
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
                $this->postJson('auth/login', ['email' => "user{$i}@x.com"])->getStatusCode()
            );
        }

        $oneMoreEmail = 'user'.(RestifyApplicationServiceProvider::LOGIN_ATTEMPTS_PER_IP_PER_MINUTE + 1).'@x.com';

        $this->postJson('auth/login', ['email' => $oneMoreEmail])
            ->assertTooManyRequests();
    }

    #[Test]
    public function exhausting_the_login_limiter_does_not_throttle_forgot_password(): void
    {
        Route::restifyAuth('auth', ['login', 'forgotPassword']);

        foreach (range(1, RestifyApplicationServiceProvider::AUTH_ATTEMPTS_PER_MINUTE + 1) as $attempt) {
            $this->postJson('auth/login', ['email' => 'a@x.com']);
        }

        $this->assertNotSame(
            JsonResponse::HTTP_TOO_MANY_REQUESTS,
            $this->postJson('auth/forgotPassword', ['email' => 'a@x.com'])->getStatusCode()
        );
    }

    #[Test]
    public function an_app_defined_restify_login_limiter_wins_over_the_package_default(): void
    {
        RateLimiter::for('restify.login', fn (): Limit => Limit::perMinute(1));

        $provider = new RestifyApplicationServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'authRateLimiters');
        $method->invoke($provider);

        Route::restifyAuth('auth', ['login']);

        $this->assertNotSame(
            JsonResponse::HTTP_TOO_MANY_REQUESTS,
            $this->postJson('auth/login', ['email' => 'a@x.com'])->getStatusCode()
        );

        $this->postJson('auth/login', ['email' => 'a@x.com'])
            ->assertTooManyRequests();
    }
}
