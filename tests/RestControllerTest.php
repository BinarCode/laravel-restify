<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\Fixtures\UserController;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mockery;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestControllerTest extends IntegrationTest
{
    /**
     * @var UserController
     */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = resolve(UserController::class);
    }

    public function test_response_contains_data_array()
    {
        $user = factory(User::class)->create();
        $response = $this->controller->index();
        $this->assertIsArray(data_get($response->getData(), 'data'));
        $users = data_get($response->getData(), 'data');
        $this->assertEquals($user->email, data_get(end($users), 'email'));
    }

    public function test_single_object_in_response()
    {
        $response = $this->controller->store(Mockery::mock(Request::class));
        $this->assertIsObject(data_get($response->getData(), 'data'));
    }

    public function test_authenticated_user_exists()
    {
        $this->assertNull($this->controller->user());
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $this->assertSame($this->controller->user(), $user);
    }

    public function test_gate_for_a_null_model_throw()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->controller->show(1);
    }

    public function test_gate_restrict_access()
    {
        $user = factory(User::class)->create();

        Gate::shouldReceive('check')
            ->andReturnFalse();
        $this->expectException(GatePolicy::class);
        $this->expectExceptionMessage(__('messages.no_model_access'));
        $this->controller->show($user->id);
    }

    public function test_gate_allow_access()
    {
        $user = factory(User::class)->create();
        Gate::shouldReceive('check')
            ->andReturnTrue();

        $this->assertTrue($this->controller->gate('access', $user));
        $response = $this->controller->show($user->id);
        $this->assertEquals($user->email, data_get($response->getData(), 'data.email'));
    }

    public function test_making_custom_response()
    {
        $user = factory(User::class)->create();

        Gate::shouldReceive('check')
            ->andReturnFalse();
        $response = $this->controller->update(new FormRequest(), $user->id);

        $this->assertEquals($response->getStatusCode(), RestResponse::REST_RESPONSE_MISSING_CODE);
        $this->assertSame($response->getData()->errors, ['Entity not found.']);
    }

    public function test_making_custom_response_message()
    {
        $user = factory(User::class)->create();

        Gate::shouldReceive('check')
            ->andReturnTrue();
        $response = $this->controller->destroy($user->id);
        $this->assertSame($response->getData()->message, 'User deleted.');
    }

    public function test_can_access_config_repository()
    {
        $this->assertInstanceOf(Repository::class, $this->controller->config());
    }

    public function test_can_access_request()
    {
        $this->assertInstanceOf(RestifyRequest::class, $this->controller->request());
    }

    public function test_broker_exists()
    {
        $this->assertInstanceOf(PasswordBroker::class, $this->controller->broker());
    }
}
