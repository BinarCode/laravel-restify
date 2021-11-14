<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\DataTransferObject\DataTransferObject;

class RelatedDto extends DataTransferObject
{
    public array $related = [];
}
