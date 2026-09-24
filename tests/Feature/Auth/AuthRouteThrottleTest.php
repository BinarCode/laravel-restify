<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

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
    #[TestWith(['registerRoute.stub'], 'register')]
    #[TestWith(['loginRoute.stub'], 'login')]
    #[TestWith(['verifyRoute.stub'], 'verifyEmail')]
    #[TestWith(['forgotPasswordRoute.stub'], 'forgotPassword')]
    #[TestWith(['resetPasswordRoute.stub'], 'resetPassword')]
    public function the_route_published_by_restify_auth_is_throttled(string $stub): void
    {
        $this->assertStringContainsString(
            "->middleware('throttle:6,1')",
            file_get_contents(__DIR__.'/../../../src/Commands/stubs/Routes/'.$stub),
        );
    }
}
