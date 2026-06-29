<?php

namespace Binaryk\LaravelRestify\Auth\Social;

use Binaryk\LaravelRestify\Models\SocialAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Default strategy for turning a provider (Socialite) user into one of the
 * application's authenticatable users and linking the provider account.
 *
 * The resolution order is:
 *   1. An existing linked account for this (provider, provider_id) -> its user.
 *   2. An existing application user matching the provider email -> link + return.
 *   3. Otherwise create a brand new user -> link + return.
 *
 * Override any single step by extending this class and pointing
 * `restify.auth.social.resolver` at your version, or replace the whole thing at
 * runtime with `Restify::resolveSocialUserUsing(...)`.
 */
class SocialUserResolver
{
    public function resolve(string $provider, SocialiteUser $socialiteUser): Authenticatable
    {
        $accountModel = config('restify.auth.social.model');

        /** @var SocialAccount|null $account */
        $account = $accountModel::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $socialiteUser->getId())
            ->first();

        if ($account && $user = $account->user) {
            $this->linkAccount($provider, $socialiteUser, $user);

            return $user;
        }

        $user = $this->findUser($socialiteUser) ?? $this->createUser($provider, $socialiteUser);

        $this->linkAccount($provider, $socialiteUser, $user);

        return $user;
    }

    /**
     * Find an existing application user that should own this provider account.
     */
    protected function findUser(SocialiteUser $socialiteUser): ?Authenticatable
    {
        if (! $email = $socialiteUser->getEmail()) {
            return null;
        }

        $userModel = config('restify.auth.user_model');

        return $userModel::query()->where('email', $email)->first();
    }

    /**
     * Create a fresh application user from the provider profile. OAuth providers
     * have already verified the email, so it is marked as verified here.
     */
    protected function createUser(string $provider, SocialiteUser $socialiteUser): Authenticatable
    {
        $userModel = config('restify.auth.user_model');

        $user = new $userModel;

        $user->forceFill([
            'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'User',
            'email' => $socialiteUser->getEmail(),
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => $socialiteUser->getEmail() ? now() : null,
        ])->save();

        return $user;
    }

    /**
     * Create or refresh the stored provider account for the given user.
     */
    protected function linkAccount(string $provider, SocialiteUser $socialiteUser, Authenticatable $user): void
    {
        $accountModel = config('restify.auth.social.model');

        $accountModel::query()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => (string) $socialiteUser->getId(),
            ],
            [
                'user_id' => $user->getAuthIdentifier(),
                'nickname' => $socialiteUser->getNickname(),
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'avatar' => $socialiteUser->getAvatar(),
                'access_token' => $socialiteUser->token ?? null,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'expires_at' => isset($socialiteUser->expiresIn) && $socialiteUser->expiresIn
                    ? now()->addSeconds($socialiteUser->expiresIn)
                    : null,
            ]
        );
    }
}
