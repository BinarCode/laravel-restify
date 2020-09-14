<?php

namespace Binaryk\LaravelRestify\Fields;

class BelongsToMany extends Field
{
    public function __construct($attribute, callable $resolveCallback = null, $repository = null)
    {
        parent::__construct($attribute, $resolveCallback);
    }
}
