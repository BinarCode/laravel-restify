<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait FindsUserByEmail
{
    /**
     * @template TUser of Model
     *
     * @param  class-string<TUser>  $userModel
     * @return TUser|null
     */
    protected function findUserByEmail(string $userModel, string $email): ?Model
    {
        $lowercasedEmail = Str::lower($email);

        $users = $userModel::query()
            ->whereIn('email', array_values(array_unique([$email, $lowercasedEmail])))
            ->get();

        return self::userMatchingEmail($users, $email);
    }

    /**
     * @template TUser of Model
     *
     * @param  Collection<int, TUser>  $users
     * @return TUser|null
     */
    private static function userMatchingEmail(Collection $users, string $email): ?Model
    {
        $lowercasedEmail = Str::lower($email);

        return $users->first(static fn (Model $user): bool => $user->getAttribute('email') === $email)
            ?? $users->first(static fn (Model $user): bool => \is_string($user->getAttribute('email')) && Str::lower($user->getAttribute('email')) === $lowercasedEmail);
    }
}
