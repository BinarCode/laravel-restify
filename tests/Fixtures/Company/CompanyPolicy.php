<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Collection;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can use restify feature for each CRUD operation.
     * So if this is not allowed, all operations will be disabled.
     *
     * @param  User  $user
     * @return mixed
     */
    public function allowRestify(User $user = null)
    {
        return true;
    }

    /**
     * Determine whether the user can get the model.
     *
     * @return mixed
     */
    public function show(User $user, Company $model)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function store(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can create multiple models at once.
     *
     * @return mixed
     */
    public function storeBulk(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Company $model)
    {
        return true;
    }

    /**
     * Determine whether the user can update bulk the model.
     *
     * @return mixed
     */
    public function updateBulk(User $user, Company $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Company $model)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Company $model)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Company $model)
    {
        return true;
    }

    public function attachUsers(User $user, Company $model, User $userToBeAttached)
    {
        return $_SERVER['allow_attach_users'] ?? true;
    }

    public function syncUsers(User $user, Company $model, Collection $keys)
    {
        return $_SERVER['allow_sync_users'] ?? true;
    }

    public function detachUsers(User $user, Company $model, User $userToBeDetached)
    {
        return $_SERVER['allow_detach_users'] ?? true;
    }
}
