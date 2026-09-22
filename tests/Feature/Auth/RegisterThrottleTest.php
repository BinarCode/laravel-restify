<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class RegisterThrottleTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->withMiddleware(ThrottleRequests::class);

        Route::restifyAuth('auth', ['register']);
    }

    #[Test]
    public function register_is_throttled_like_the_other_auth_routes(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->postJson('auth/register')->assertUnprocessable();
        }

        $this->postJson('auth/register')->assertStatus(JsonResponse::HTTP_TOO_MANY_REQUESTS);
    }
}
