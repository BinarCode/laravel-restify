<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RepositoryDetachRequest extends RestifyRequest
{
    /**
     * @return Collection<int, Model|null>
     */
    public function detachRelatedModels(): Collection
    {
        $relatedRepository = $this->repository(
            Restify::repositoryForTable($table = $this->relatedRepository)::uriKey()
        );

        if (is_null($relatedRepository)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$table}] table");
        }

        $ids = Arr::wrap($this->input($this->relatedRepository));

        /** @var Collection<int, Model> $models */
        $models = $relatedRepository->model()->newModelQuery()->whereKey($ids)->get();

        return Collection::make($ids)->map(
            fn ($id) => $models->first(static fn (Model $model): bool => $model->getKey() == $id)
        );
    }
}
