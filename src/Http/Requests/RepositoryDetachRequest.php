<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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

    /**
     * @return Builder<Model>
     */
    protected function relatedModelQuery(Model $model): Builder
    {
        return $model->newQueryWithoutRelationships()->withoutGlobalScope(SoftDeletingScope::class);
    }
}
