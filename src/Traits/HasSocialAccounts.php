<?php

namespace Binaryk\LaravelRestify\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Add to your authenticatable model to expose its linked provider accounts:
 *
 *     class User extends Authenticatable
 *     {
 *         use \Binaryk\LaravelRestify\Traits\HasSocialAccounts;
 *     }
 *
 *     $user->socialAccounts;            // all linked providers
 *     $user->hasSocialProvider('github');
 *     $user->socialAccount('github');   // the GitHub account, if linked
 */
trait HasSocialAccounts
{
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(config('restify.auth.social.model'), 'user_id');
    }

    public function socialAccount(string $provider)
    {
        return $this->socialAccounts()->where('provider', $provider)->first();
    }

    public function hasSocialProvider(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }
}
