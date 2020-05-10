<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PostPolicy
{
    /**
     * Determine if the given user can view resources.
     *
     * @return bool|mixed
     */
    public function allowRestify()
    {
        return $_SERVER['restify.post.allowRestify'] ?? true;
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

    public function delete($user, $post)
    {
        return $_SERVER['restify.post.deletable'] ?? true;
    }
}
