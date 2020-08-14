<?php

namespace Binaryk\LaravelRestify\Models;

interface CreationAware
{
    public static function createWithAttributes(array $attributes): ?self;
}
