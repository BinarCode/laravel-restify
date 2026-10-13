<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositoryAttachRequest extends RestifyRequest
{
    /**
     * @return Collection<int, Model>
     */
    public function attachRelatedModels(): Collection
    {
        return $this->relatedModels();
    }
}
