<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Carbon\Carbon;

abstract class TimestampFilter extends AdvancedFilter
{
    public string $type = 'timestamp';

    public function resolve(RestifyRequest $request, AdvancedFilterPayloadDto $dto): self
    {
        $this->value = Carbon::parse($dto->value());
    }
}
