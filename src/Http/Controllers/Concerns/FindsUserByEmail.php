<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
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

        return $users->firstWhere('email', $email)
            ?? $users->firstWhere('email', $lowercasedEmail)
            ?? $users->first();
    }
}
