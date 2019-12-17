<?php

namespace Binaryk\LaravelRestify\Exceptions\Guard;

class EntityNotFoundException extends \Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $type
     */
    public function __construct($type)
    {
        parent::__construct("Guard entity with policy [{$type}] not found.");
    }
}
