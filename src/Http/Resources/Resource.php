<?php

namespace Binaryk\LaravelRestify\Http\Resources;

use Binaryk\LaravelRestify\Traits\Make;
use Illuminate\Contracts\Support\Responsable;

abstract class Resource implements Responsable
{
    use Make;

    public function toResponse($request)
    {
        return response()->json($this->toArray($request));
    }

    abstract public function toArray($request);
}
