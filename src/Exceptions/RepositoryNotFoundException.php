<?php

namespace Binaryk\LaravelRestify\Exceptions;

use RuntimeException;

class RepositoryNotFoundException extends RuntimeException
{
    public static function make(string $class): self
    {
        return new static(__('Repository :name not found.', [
            'name' => $class,
        ]));
    }
}
