<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

class PostPolicy
{
    public function allowRestify()
    {
        return $_SERVER['restify.post.allowRestify'] ?? true;
    }

    public function show(): bool
    {
        return $_SERVER['restify.post.show'] ?? true;
    }

    public function store(): bool
    {
        return $_SERVER['restify.post.store'] ?? true;
    }

    public function storeBulk($user): bool
    {
        return $_SERVER['restify.post.storeBulk'] ?? true;
    }

    public function update($user, $post)
    {
        return $_SERVER['restify.post.update'] ?? true;
    }

    public function delete($user, $post)
    {
        return $_SERVER['restify.post.delete'] ?? true;
    }

    public function attachUser(object $user = null, $post, $userToAttach)
    {
        return $_SERVER['restify.post.allowAttachUser'] ?? true;
    }
}
