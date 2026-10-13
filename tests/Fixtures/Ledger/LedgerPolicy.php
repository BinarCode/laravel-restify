<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Ledger;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Model;

class LedgerPolicy
{
    public function allowRestify(?User $user = null): bool
    {
        return true;
    }

    public function show(User $user, Model $model): bool
    {
        return true;
    }

    public function store(User $user): bool
    {
        return true;
    }

    public function storeBulk(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return true;
    }

    public function updateBulk(User $user, Model $model): bool
    {
        return true;
    }

    public function delete(User $user, Model $model): bool
    {
        return true;
    }

    public function deleteBulk(User $user, Model $model): bool
    {
        return true;
    }

    public function attachTags(User $user, Model $model, Model $related): bool
    {
        return true;
    }

    public function detachTags(User $user, Model $model, Model $related): bool
    {
        return true;
    }
}
