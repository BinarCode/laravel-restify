<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use BackedEnum;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\ValidatesRelatedKeyShape;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Stringable;

trait ResolvesRelatedModels
{
    use ValidatesRelatedKeyShape;

    private const RESOLVED_RELATED_MODELS_CACHE_ATTRIBUTE = '_restifyResolvedRelatedModels';

    /**
     * The related models named in the request, in the order they were sent.
     *
     * @return Collection<int, Model>
     *
     * @throws ModelNotFoundException if any of the requested ids does not exist.
     */
    protected function relatedModels(): Collection
    {
        $cached = $this->attributes->get(self::RESOLVED_RELATED_MODELS_CACHE_ATTRIBUTE);

        if ($cached instanceof Collection) {
            return $cached;
        }

        $relatedRepositoryKey = $this->relatedRepositoryKey();

        $relatedRepositoryClass = Restify::repositoryClassForKey($relatedRepositoryKey);

        if (is_null($relatedRepositoryClass)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$relatedRepositoryKey}] key");
        }

        $requestedIds = array_values(Arr::wrap($this->input($relatedRepositoryKey)));

        $ids = array_map(
            fn (mixed $id): int|string => $this->assertValidRelatedKeyShape($id, $relatedRepositoryKey),
            $requestedIds,
        );

        $model = $this->repository($relatedRepositoryKey)->model();

        $models = $model->newModelQuery()->whereKey($ids)->get();

        $resolved = $this->matchRelatedModels($ids, $models, $model, caseInsensitive: false);

        $unmatched = array_diff_key($ids, $resolved);

        // A string key the database collation matches but PHP does not (e.g.
        // "ABC-UUID" against a stored "abc-uuid") gets one batched retry that lets
        // the database decide. An int key needs none: the cast above already
        // mirrors how the database coerces it.
        if ($unmatched !== [] && ! $this->hasIntegerKey($model)) {
            $retried = $model->newModelQuery()->whereKey(array_values($unmatched))->get();

            $resolved += $this->matchRelatedModels($unmatched, $retried, $model, caseInsensitive: true);

            $unmatched = array_diff_key($ids, $resolved);
        }

        if ($unmatched !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, array_values($unmatched));
        }

        ksort($resolved);

        $result = Collection::make(array_values($resolved));

        $this->attributes->set(self::RESOLVED_RELATED_MODELS_CACHE_ATTRIBUTE, $result);

        return $result;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @param  Collection<int, Model>  $models
     * @return array<int, Model> the matched models, keyed by the position of the id they match.
     */
    private function matchRelatedModels(array $ids, Collection $models, Model $model, bool $caseInsensitive): array
    {
        $modelsByKey = [];

        foreach ($models as $relatedModel) {
            $modelsByKey[$this->normalizeRelatedModelKey($relatedModel->getKey(), $model, $caseInsensitive)] = $relatedModel;
        }

        $matched = [];

        foreach ($ids as $position => $id) {
            $key = $this->normalizeRelatedModelKey($id, $model, $caseInsensitive);

            if (isset($modelsByKey[$key])) {
                $matched[$position] = $modelsByKey[$key];
            }
        }

        return $matched;
    }

    private function normalizeRelatedModelKey(mixed $key, Model $model, bool $caseInsensitive): int|string
    {
        if ($key instanceof BackedEnum) {
            $key = $key->value;
        } elseif ($key instanceof Stringable) {
            $key = (string) $key;
        }

        if (! is_int($key) && ! is_string($key)) {
            throw new InvalidArgumentException('The related model key must be an int or a string.');
        }

        if ($this->hasIntegerKey($model)) {
            return (int) $key;
        }

        return $caseInsensitive ? mb_strtolower((string) $key) : (string) $key;
    }

    private function hasIntegerKey(Model $model): bool
    {
        return in_array($model->getKeyType(), ['int', 'integer'], true);
    }
}
