<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Http\Requests\Concerns\ResolvesRelatedModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositorySyncRequest extends RestifyRequest
{
    use ResolvesRelatedModels;

    /**
     * @return Collection<int, Model|null>
     */
    public function syncRelatedModels(): Collection
    {
        return $this->relatedModels();
    }
}
