<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait ResolvesRelatedModels
{
    /**
     * The related models named in the request, in the order they were sent, with a
     * null wherever the database held no such row.
     *
     * @return Collection<int, Model|null>
     */
    protected function relatedModels(): Collection
    {
        $table = $this->relatedRepositoryKey();

        $relatedRepositoryClass = Restify::repositoryForTable($table);

        if (is_null($relatedRepositoryClass)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$table}] table");
        }

        $ids = Arr::wrap($this->input($table));

        $model = $this->repository($relatedRepositoryClass::uriKey())->model();

        /** @var Collection<int, Model> $models */
        $models = $model->newModelQuery()->whereKey($ids)->get();

        $byKey = $models->keyBy($model->getKeyName());

        return Collection::make($ids)->map(
            static fn (mixed $id): ?Model => $byKey->get($id)
                ?? $model->newModelQuery()->whereKey($id)->first()
        );
    }
}
