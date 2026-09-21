<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Http\Requests\Concerns\ResolvesRelatedModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositoryAttachRequest extends RestifyRequest
{
    use ResolvesRelatedModels;

    /**
     * @return Collection<int, Model|null>
     */
    public function attachRelatedModels(): Collection
    {
        return $this->relatedModels();
    }
}
