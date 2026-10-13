<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositoryDetachRequest extends RestifyRequest
{
    /**
     * @return Collection<int, Model>
     */
    public function detachRelatedModels(): Collection
    {
        return $this->relatedModels();
    }
}
