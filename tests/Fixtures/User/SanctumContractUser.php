<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use LogicException;

class SanctumContractUser extends Authenticatable implements HasApiTokens
{
    protected $table = 'users';

    protected $guarded = [];

    /**
     * @return MorphMany<PersonalAccessToken, $this>
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    public function tokenCan(string $ability): bool
    {
        return false;
    }

    /**
     * @param  list<string>  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        throw new LogicException('Not used by the fixture.');
    }

    public function currentAccessToken(): ?HasAbilities
    {
        return null;
    }

    /**
     * @param  HasAbilities  $accessToken
     */
    public function withAccessToken($accessToken): static
    {
        return $this;
    }
}
