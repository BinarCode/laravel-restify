<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

class UserPolicy
{
    /**
     * Determine if the given user can use repository.
     *
     * @param User|null $user
     * @return bool|mixed
     */
    public function allowRestify($user = null)
    {
        return $_SERVER['restify.users.allowRestify'] ?? true;
    }

    /**
     * Determine if user can be shown.
     */
    public function show($user = null)
    {
        return $_SERVER['restify.users.show'] ?? true;
    }

    /**
     * Determine if users can be created.
     */
    public function store($user = null)
    {
        return $_SERVER['restify.users.store'] ?? true;
    }

    /**
     * Determine if users can be stored bulk.
     */
    public function storeBulk($user)
    {
        return $_SERVER['restify.users.storeBulk'] ?? true;
    }

    public function update($user, $model)
    {
        return $_SERVER['restify.users.update'] ?? true;
    }

    public function delete($user, $model)
    {
        return $_SERVER['restify.users.deletable'] ?? true;
    }
}
