<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\ValidatesRelatedKeyShape;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait ResolvesRelatedModels
{
    use ValidatesRelatedKeyShape;

    /**
     * The related models named in the request, in the order they were sent.
     *
     * @return Collection<int, Model>
     *
     * @throws ModelNotFoundException if any of the requested ids does not exist.
     */
    protected function relatedModels(): Collection
    {
        $relatedRepositoryKey = $this->relatedRepositoryKey();

        $relatedRepositoryClass = Restify::repositoryClassForKey($relatedRepositoryKey);

        if (is_null($relatedRepositoryClass)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$relatedRepositoryKey}] key");
        }

        $ids = Collection::make(Arr::wrap($this->input($relatedRepositoryKey)))
            ->map(fn (mixed $id): int|string => $this->assertValidRelatedKeyShape($id, $relatedRepositoryKey))
            ->all();

        $model = $this->repository($relatedRepositoryKey)->model();

        /** @var Collection<int, Model> $models */
        $models = $model->newModelQuery()->whereKey($ids)->get();

        // Normalize both sides of the lookup the same way the database already
        // normalized $ids to match the model's key (e.g. "05" and 5 are the same
        // int key): a bare keyBy() would otherwise miss a non-canonical numeric
        // string id, since PHP does not coerce it to the matching array key.
        $byKey = $models->keyBy(fn (Model $relatedModel): int|string => $this->normalizeRelatedModelKey($relatedModel->getKey(), $model));

        $resolved = [];
        $missing = [];

        foreach ($ids as $id) {
            $match = $byKey->get($this->normalizeRelatedModelKey($id, $model));

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

    private function normalizeRelatedModelKey(mixed $key, Model $model): int|string
    {
        if (! is_int($key) && ! is_string($key)) {
            throw new InvalidArgumentException('The related model key must be an int or a string.');
        }

        return $model->getKeyType() === 'int' ? (int) $key : (string) $key;
    }
}
