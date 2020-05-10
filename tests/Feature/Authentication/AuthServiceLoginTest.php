<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authentication;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Events\UserLogout;
use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;
use Binaryk\LaravelRestify\Exceptions\SanctumUserException;
use Binaryk\LaravelRestify\Exceptions\UnverifiedUser;
use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SimpleUser as User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthServiceLoginTest extends IntegrationTest
{
    /**
     * @var AuthService
     */
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = resolve(AuthService::class);
    }

    public function test_password_broker_facade()
    {
        $this->assertInstanceOf(PasswordBroker::class, $this->authService->broker());
    }

    public function test_login_throw_invalid_credentials_exception()
    {
        $this->expectException(CredentialsDoesntMatch::class);
        Auth::shouldReceive('attempt')
            ->andReturnFalse();
        $this->authService->login([
            'email' => 'random@random.com',
            'password' => 'secret',
        ]);
    }

    public function test_user_did_not_verified_email()
    {
        $this->expectException(UnverifiedUser::class);
        $this->expectExceptionMessage('The email is not verified');

        $userMustVerify = (new class extends User implements MustVerifyEmail {
            use \Illuminate\Auth\MustVerifyEmail;
        });

        $userMustVerify->fill([
            'email' => 'test@mail.com',
            'email_verified_at' => null,
        ]);

        Auth::shouldReceive('attempt')
            ->andReturnTrue();

        Auth::shouldReceive('user')
            ->andReturn($userMustVerify);

        $this->authService->login([]);
    }

    public function test_login_user_did_not_user_passport_trait_or_not_implement_pasportable()
    {
        $this->app['config']->set('restify.auth.provider', 'passport');
        $this->expectException(PassportUserException::class);
        $userMustVerify = (new class extends User implements MustVerifyEmail {
            use \Illuminate\Auth\MustVerifyEmail;
        });

        $userMustVerify->fill([
            'email' => 'test@mail.com',
            'email_verified_at' => Carbon::now(),
        ]);

        Auth::shouldReceive('attempt')
            ->andReturnTrue();

        Auth::shouldReceive('user')
            ->andReturn($userMustVerify);

        $this->authService->login();
    }

    public function test_login_user_did_not_user_passport_trait_or_not_implement_sanctumable()
    {
        $this->app['config']->set('restify.auth.provider', 'sanctum');
        $this->expectException(SanctumUserException::class);

        $userMustVerify = (new class extends User implements MustVerifyEmail {
            use \Illuminate\Auth\MustVerifyEmail;
        });

        $userMustVerify->fill([
            'email' => 'test@mail.com',
            'email_verified_at' => Carbon::now(),
        ]);

        Auth::shouldReceive('attempt')
            ->andReturnTrue();

        Auth::shouldReceive('user')
            ->andReturn($userMustVerify);

        $this->authService->login();
    }

    public function test_login_with_success()
    {
        Event::fake([
            UserLoggedIn::class,
        ]);
        $user = (new class extends User implements MustVerifyEmail, Passportable {
            use \Illuminate\Auth\MustVerifyEmail;
        });

        $user->fill([
            'email' => 'test@mail.com',
            'email_verified_at' => Carbon::now(),
        ]);

        Auth::shouldReceive('attempt')
            ->andReturnTrue();

        Auth::shouldReceive('user')
            ->andReturn($user);

        $authToken = $this->authService->login();
        $this->assertEquals('token', $authToken);

        Event::assertDispatched(UserLoggedIn::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user->email);

            return $e->user instanceof User;
        });
    }

    public function test_logout_success()
    {
        Event::fake();
        $this->app['config']->set('restify.auth.provider', 'passport');

        $user = (new class extends \ Binaryk\LaravelRestify\Tests\Fixtures\User\User {
            public function tokens()
            {
                $builder = \Mockery::mock(Builder::class);
                $tokens = [(new class {
                    public function revoke()
                    {
                        return true;
                    }
                })];

                $builder->shouldReceive('get')->andReturn(collect($tokens));

                return $builder;
            }
        });

        Auth::shouldReceive('user')
            ->andReturn($user);

        $this->authService->logout();

        Event::assertDispatched(UserLogout::class);
    }

    public function test_logout_unauthenticated()
    {
        Auth::shouldReceive('user')
            ->andReturn(null);

        $this->expectException(AuthenticatableUserException::class);
        $this->authService->logout();
    }
}
