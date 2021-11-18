<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\DataTransferObject\DataTransferObject;

class AdvancedFilterPayloadDto extends DataTransferObject
{
    public string $key;

    public mixed $value;
}
