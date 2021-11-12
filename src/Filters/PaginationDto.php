<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\DataTransferObject\DataTransferObject;

class PaginationDto extends DataTransferObject
{
    public int|string|null $perPage;

    public int|string|null $page;
}
