<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Support\Collection;

class RepositoryUpdateBulkRequest extends RestifyRequest
{
    public function collectInput(): Collection
    {
        return Collection::make($this->payload());
    }
}
