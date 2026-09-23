<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ResolvesRelatedModels
{
    /**
     * The related models named in the request, in the order they were sent.
     *
     * @return Collection<int, Model>
     *
     * @throws ModelNotFoundException if any of the requested ids does not exist.
     */
    protected function relatedModels(): Collection
    {
        $table = $this->relatedRepository;

        $relatedRepositoryClass = Restify::repositoryForTable($table);

        if (is_null($relatedRepositoryClass)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$table}] table");
        }

        $ids = Arr::wrap($this->input($table));

        foreach ($ids as $id) {
            if (! is_int($id) && ! is_string($id)) {
                throw ValidationException::withMessages([__('Each attached id must be an int or a string.')]);
            }
        }

        $model = $this->repository($relatedRepositoryClass::uriKey())->model();

        /** @var Collection<int, Model> $models */
        $models = $model->newModelQuery()->whereKey($ids)->get();

        $byKey = $models->keyBy($model->getKeyName());

        $resolved = [];
        $missing = [];

        foreach ($ids as $id) {
            $match = $byKey->get($id) ?? $model->newModelQuery()->whereKey($id)->first();

            if (is_null($match)) {
                $missing[] = $id;

                continue;
            }

            $resolved[] = $match;
        }

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, $missing);
        }

        return Collection::make($resolved);
    }
}
