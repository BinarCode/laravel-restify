<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PostPolicy
{
    /**
     * Determine if the given user can view resources.
     */
    public function viewAny($user)
    {
        return $_SERVER['restify.post.viewAnyable'] ?? true;
    }

    /**
     * Determine if posts can be created.
     */
    public function store($user)
    {
        return $_SERVER['restify.post.creatable'] ?? true;
    }

    public function update($user, $post)
    {
        return $_SERVER['restify.post.updateable'] ?? true;
    }
}
