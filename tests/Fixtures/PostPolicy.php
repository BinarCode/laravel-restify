<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

/**
 * @package Binaryk\LaravelRestify\Tests\Fixtures;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PostPolicy
{
    /**
     * Determine if the given user can view resources.
     */
    public function viewAny($user)
    {
        return $_SERVER['restify.user.viewAnyable'] ?? true;
    }

    /**
     * Determine if users can be created.
     */
    public function create($user)
    {
        return $_SERVER['restify.user.creatable'] ?? true;
    }
}
