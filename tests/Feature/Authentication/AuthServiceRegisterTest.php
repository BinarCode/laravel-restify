<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authentication;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Models\LaravelRestifyModel;
use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SampleUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthServiceRegisterTest extends IntegrationTest
{
    use InteractsWithContainer;
    /**
     * @var AuthService
     */
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = resolve(AuthService::class);
    }

    public function test_register_throw_user_not_authenticatable()
    {
        $this->app->instance(User::class, (new class extends SampleUser implements Passportable {
        }));

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'remember_token' => Str::random(10),
        ];

        $request = new Request([], []);

        $request->merge($user);

        $this->expectException(AuthenticatableUserException::class);
        $this->authService->register($request);
    }

    public function test_user_query_throw_container_does_not_have_model_reflection_exception()
    {
        $this->app['config']->set('auth.providers.users.model', null);
        $this->expectException(EntityNotFoundException::class);
        $this->authService->userQuery();
    }

    public function test_user_query_throw_container_cannot_instantiate_abstract_model()
    {
        $this->app['config']->set('auth.providers.users.model', LaravelRestifyModel::class);
        $this->expectException(EntityNotFoundException::class);
        $this->authService->userQuery();
    }

    public function test_register_successfully()
    {
        Event::fake([
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'remember_token' => Str::random(10),
        ];

        $request = new Request([], []);

        $request->merge($user);

        $this->authService->register($request);

        Event::assertDispatched(Registered::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user['email']);

            return $e->user instanceof \ Binaryk\LaravelRestify\Tests\Fixtures\User\User;
        });

        $lastUser = User::query()->get()->last();

        $this->assertEquals($lastUser->email, $user['email']);
    }

    public function test_verify_user_throw_hash_not_match()
    {
        Event::fake([
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'remember_token' => Str::random(10),
        ];

        $request = new Request([], []);

        $request->merge($user);

        $this->authService->register($request);
        $lastUser = User::query()->get()->last();

        $this->expectException(AuthorizationException::class);
        $this->authService->verify($request, $lastUser->id, sha1('random@email.com'));
    }

    public function test_verify_user_successfully()
    {
        Event::fake([
            Verified::class,
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'remember_token' => Str::random(10),
        ];

        $request = new Request([], []);

        $request->merge($user);

        $this->authService->register($request);
        $lastUser = User::query()->get()->last();

        $this->assertNull($lastUser->email_verified_at);
        $this->authService->verify($request, $lastUser->id, sha1('eduard.lupacescu@binarcode.com'));
        $lastUser->refresh();
        $this->assertNotNull($lastUser->email_verified_at);
        Event::assertDispatched(Verified::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user['email']);

            return $e->user instanceof \ Binaryk\LaravelRestify\Tests\Fixtures\User\User;
        });
    }
}
