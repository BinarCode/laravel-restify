<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Carbon\Carbon;

abstract class TimestampFilter extends Filter
{
    public $type = 'timestamp';

    public function resolve(RestifyRequest $request, $value)
    {
        $this->value = Carbon::parse($value);
    }
}
