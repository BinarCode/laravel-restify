<?php

namespace Binaryk\LaravelRestify\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
class Model
{
    public readonly string $modelClass;

    public function __construct(string $modelClass)
    {
        if (empty($modelClass)) {
            throw new InvalidArgumentException('Model class cannot be empty');
        }

        $this->modelClass = $modelClass;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
