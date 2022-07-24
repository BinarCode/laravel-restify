<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Tests\Prototypes\PostPrototype;

trait Prototypes
{
    public function posts(): PostPrototype
    {
        return PostPrototype::make($this);
    }
}
