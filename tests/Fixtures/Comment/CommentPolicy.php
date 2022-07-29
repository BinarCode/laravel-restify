<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Comment;

class CommentPolicy
{
    public function allowRestify($user = null)
    {
        return $_SERVER['restify.comment.allowRestify'] ?? true;
    }

    public function show(): bool
    {
        return $_SERVER['restify.comment.show'] ?? true;
    }

    public function store(): bool
    {
        return $_SERVER['restify.comment.store'] ?? true;
    }

    public function storeBulk($user): bool
    {
        return $_SERVER['restify.comment.storeBulk'] ?? true;
    }

    public function update($user, $model)
    {
        return $_SERVER['restify.comment.update'] ?? true;
    }

    public function updateBulk($user): bool
    {
        return $_SERVER['restify.comment.updateBulk'] ?? true;
    }

    public function deleteBulk($user, $model)
    {
        return $_SERVER['restify.comment.deleteBulk'] ?? true;
    }

    public function delete($user, $model)
    {
        return $_SERVER['restify.comment.delete'] ?? true;
    }

    public function attachUser(object $user = null, $model, $userToAttach)
    {
        return $_SERVER['restify.comment.allowAttachUser'] ?? true;
    }
}
