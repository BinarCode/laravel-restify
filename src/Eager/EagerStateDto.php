<?php

namespace Binaryk\LaravelRestify\Eager;

use Spatie\DataTransferObject\DataTransferObject;

class EagerStateDto extends DataTransferObject
{
    public string $queryRelation;
}
