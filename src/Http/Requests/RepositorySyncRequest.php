<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Http\Requests\Concerns\ResolvesRelatedModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositorySyncRequest extends RestifyRequest
{
    use ResolvesRelatedModels;

    /**
     * @return Collection<int, Model>
     */
    public function syncRelatedModels(): Collection
    {
        return $this->relatedModels();
    }

    /**
     * @param  Collection<int, mixed>  $relatedKeys
     * @return Collection<int, Model>
     */
    public function resolveSyncRelatedModels(Collection $relatedKeys): Collection
    {
        $requestedIds = array_values($relatedKeys->all());

        if ($requestedIds === $this->requestedRelatedIds()) {
            return $this->relatedModels();
        }

        return $this->resolveRelatedModels($requestedIds);
    }
}
