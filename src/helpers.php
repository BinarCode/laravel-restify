<?php

use Binaryk\LaravelRestify\Fields\Field;

if (!function_exists("field")) {
    function field(...$args)
    {
        return Field::new(...$args);
    }
}
