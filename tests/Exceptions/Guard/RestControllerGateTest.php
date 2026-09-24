<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions\Guard;

use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserController;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RestControllerGateTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);
    }

    protected function tearDown(): void
    {
        config(['app.debug' => false]);

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        $router->get('/gate-test/{id}', [UserController::class, 'show']);
    }

    #[Test]
    public function guarding_a_missing_model_throws_the_real_exception(): void
    {
        $this->getJson('/gate-test/999999')
            ->assertStatus(500)
            ->assertJson([
                'exception' => EntityNotFoundException::class,
                'message' => 'Guard entity with policy [access] not found.',
            ]);
    }

    #[Test]
    public function guarding_a_model_against_an_undefined_policy_ability_throws_the_real_exception(): void
    {
        $user = User::factory()->create();

        $this->getJson("/gate-test/{$user->id}")
            ->assertStatus(500)
            ->assertJson([
                'exception' => GatePolicy::class,
                'message' => 'messages.no_model_access',
            ]);
    }
}
