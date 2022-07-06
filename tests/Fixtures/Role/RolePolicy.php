<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Role;

class RolePolicy
{
    public function allowRestify($user): bool
    {
        return $_SERVER['restify.roles.allowRestify'] ?? true;
    }

    public function show($user = null): bool
    {
        return $_SERVER['restify.roles.show'] ?? true;
    }

    public function store($user = null): bool
    {
        return $_SERVER['restify.roles.store'] ?? true;
    }

    public function storeBulk($user): bool
    {
        return $_SERVER['restify.roles.storeBulk'] ?? true;
    }

    public function update($user, $model): bool
    {
        return $_SERVER['restify.roles.update'] ?? true;
    }

    public function delete($user, $model): bool
    {
        return $_SERVER['restify.roles.delete'] ?? true;
    }
}
