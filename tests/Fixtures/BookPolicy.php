<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class BookPolicy
{
    /**
     * Determine if the given user can view resources.
     */
    public function viewAny($user)
    {
        return $_SERVER['restify.book.viewAnyable'] ?? true;
    }

    /**
     * Determine if users can be created.
     */
    public function store($user)
    {
        return $_SERVER['restify.book.storable'] ?? true;
    }

    /**
     * Determine if books can be updated.
     */
    public function update($user, $book)
    {
        return $_SERVER['restify.book.updateable'] ?? true;
    }

    /**
     * Determine if book can be shown.
     */
    public function show($user, $book)
    {
        return $_SERVER['restify.book.showable'] ?? true;
    }

    /**
     * Determine if books can be deleted.
     */
    public function destroy($user)
    {
        return $_SERVER['restify.book.destroyable'] ?? true;
    }
}
