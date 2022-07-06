<?php

namespace Binaryk\LaravelRestify\Models;

class ActionLogPolicy
{
    public function allowRestify($user): bool
    {
        return $_SERVER['restify.actionLogs.allowRestify'] ?? true;
    }

    public function show($user = null): bool
    {
        return $_SERVER['restify.actionLogs.show'] ?? true;
    }

    public function store($user = null): bool
    {
        return $_SERVER['restify.actionLogs.store'] ?? true;
    }

    public function storeBulk($user): bool
    {
        return $_SERVER['restify.actionLogs.storeBulk'] ?? true;
    }

    public function update($user, $model): bool
    {
        return $_SERVER['restify.actionLogs.update'] ?? true;
    }

    public function delete($user, $model): bool
    {
        return $_SERVER['restify.actionLogs.delete'] ?? true;
    }
}
