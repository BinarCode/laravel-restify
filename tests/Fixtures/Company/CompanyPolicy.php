<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
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
     * @return mixed
     */
    public function allowRestify(?User $user = null)
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
        $allowed = $_SERVER['allow_attach_users'] ?? true;

        return is_callable($allowed) ? $allowed($userToBeAttached) : $allowed;
    }

    public function syncUsers(User $user, Company $model, Collection $keys)
    {
        return $_SERVER['allow_sync_users'] ?? true;
    }

    public function detachUsers(User $user, Company $model, User $userToBeDetached)
    {
        $allowed = $_SERVER['allow_detach_users'] ?? true;

        return is_callable($allowed) ? $allowed($userToBeDetached) : $allowed;
    }

    public function attachRoles(User $user, Company $model, Role $roleToBeAttached)
    {
        $allowed = $_SERVER['allow_attach_roles'] ?? true;

        return is_callable($allowed) ? $allowed($roleToBeAttached) : $allowed;
    }

    public function syncRoles(User $user, Company $model, Collection $keys)
    {
        return $_SERVER['allow_sync_roles'] ?? true;
    }

    public function detachRoles(User $user, Company $model, Role $roleToBeDetached)
    {
        $allowed = $_SERVER['allow_detach_roles'] ?? true;

        return is_callable($allowed) ? $allowed($roleToBeDetached) : $allowed;
    }

    public function attachStaff(User $user, Company $model, User $staffToBeAttached)
    {
        return true;
    }

    public function syncStaff(User $user, Company $model, Collection $keys)
    {
        return true;
    }

    public function syncSecondaryStaff(User $user, Company $model, Collection $keys): bool
    {
        return true;
    }

    public function detachStaff(User $user, Company $model, User $staffToBeDetached)
    {
        return true;
    }

    public function attachLabels(User $user, Company $model, Label $labelToBeAttached)
    {
        return true;
    }

    public function syncLabels(User $user, Company $model, Collection $keys)
    {
        return true;
    }

    public function attachTiers(User $user, Company $model, Label $tierToBeAttached)
    {
        return true;
    }

    public function syncTiers(User $user, Company $model, Collection $keys)
    {
        return true;
    }

    public function attachBadges(User $user, Company $model, Label $badgeToBeAttached)
    {
        return true;
    }

    public function syncBadges(User $user, Company $model, Collection $keys)
    {
        return true;
    }

    public function attachMembers(User $user, Company $model, User $memberToBeAttached)
    {
        return true;
    }

    public function syncMembers(User $user, Company $model, Collection $keys)
    {
        return true;
    }
}
