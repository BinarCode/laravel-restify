<?php

namespace Binaryk\LaravelRestify\Exceptions\Eloquent;

class EntityNotFoundException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string $entity
     * @param  string|int $id
     * @return void
     */
    public function __construct($entity = null, $id = null)
    {
        if (env('APP_DEBUG') && $entity && $id) {
            parent::__construct("[{$entity}] with provided ID [{$id}] not found.");
            return;
        }

        parent::__construct(__('exceptions.eloquent.not_found'));
    }
}
