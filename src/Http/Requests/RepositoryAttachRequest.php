<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RepositoryAttachRequest extends RestifyRequest
{
    /**
     * @return Collection<int, Model|null>
     */
    public function attachRelatedModels(): Collection
    {
        $relatedRepository = $this->repository(
            Restify::repositoryForTable($table = $this->relatedRepository)::uriKey()
        );

        if (is_null($relatedRepository)) {
            abort(400, "Missing repository for the [{$table}] table");
        }

        $ids = Arr::wrap($this->input($this->relatedRepository));

        $models = $relatedRepository->model()->newModelQuery()->whereKey($ids)->get();

        return Collection::make($ids)->map(
            fn ($id) => $models->first(static fn (Model $model): bool => $model->getKey() == $id)
        );
    }
}
