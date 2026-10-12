<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\MatchesRelatedModels;
use Binaryk\LaravelRestify\Traits\ValidatesRelatedKeyShape;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait ResolvesRelatedModels
{
    use MatchesRelatedModels;
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

        $result = $this->resolveRelatedModels($this->requestedRelatedIds());

        $this->attributes->set(self::RESOLVED_RELATED_MODELS_CACHE_ATTRIBUTE, $result);

        return $result;
    }

    /**
     * The related model a requested id resolves to, however the id is spelled
     * ("05" for 5, "ABC" for a stored "abc" when the database collation matches).
     *
     * @internal
     *
     * @throws ModelNotFoundException if the database does not return the id.
     */
    public function relatedModelFor(int|string $id): Model
    {
        $this->relatedModels();

        $key = $this->normalizeRelatedModelKey($id, $this->relatedModelPrototype(), caseInsensitive: false);

        if (! isset($this->relatedModelsByKey()[$key])) {
            $this->resolveRelatedModels([$id]);
        }

        return $this->relatedModelsByKey()[$key];
    }

    /**
     * @return Builder<Model>
     */
    protected function relatedModelQuery(Model $model): Builder
    {
        return $model->newQuery();
    }

    private function relatedModelPrototype(): Model
    {
        $attribute = $this->relatedCacheAttribute('model');

        $cached = $this->attributes->get($attribute);

        if ($cached instanceof Model) {
            return $cached;
        }

        $model = $this->repository($this->relatedRepositoryKey())->model();

        $this->attributes->set($attribute, $model);

        return $model;
    }

    /**
     * @return array<int|string, Model> keyed by each resolved requested id and by the real key of the model it resolved to.
     */
    private function relatedModelsByKey(): array
    {
        /** @var array<int|string, Model> $relatedModelsByKey */
        $relatedModelsByKey = $this->attributes->get($this->relatedCacheAttribute('byKey'), []);

        return $relatedModelsByKey;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @param  array<int, Model>  $resolved
     */
    private function rememberRelatedModels(array $ids, array $resolved, Model $model): void
    {
        $relatedModelsByKey = $this->relatedModelsByKey();

        foreach ($resolved as $position => $relatedModel) {
            $requestedKey = $this->normalizeRelatedModelKey($ids[$position], $model, caseInsensitive: false);
            $realKey = $this->normalizeRelatedModelKey($relatedModel->getKey(), $model, caseInsensitive: false);

            $relatedModelsByKey[$requestedKey] = $relatedModel;
            $relatedModelsByKey[$realKey] ??= $relatedModel;
        }

        $this->attributes->set($this->relatedCacheAttribute('byKey'), $relatedModelsByKey);
    }

    private function relatedCacheAttribute(string $name): string
    {
        return "_restifyRelated:{$this->relatedRepositoryKey()}:{$name}";
    }

    /**
     * @return list<mixed>
     */
    protected function requestedRelatedIds(): array
    {
        return array_values(Arr::wrap($this->input($this->relatedRepositoryKey())));
    }

    /**
     * @param  list<mixed>  $requestedIds
     * @return Collection<int, Model>
     *
     * @throws ModelNotFoundException
     */
    protected function resolveRelatedModels(array $requestedIds): Collection
    {
        $relatedRepositoryKey = $this->relatedRepositoryKey();

        $relatedRepositoryClass = Restify::repositoryClassForKey($relatedRepositoryKey);

        if (is_null($relatedRepositoryClass)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$relatedRepositoryKey}] key");
        }

        $ids = array_map(
            fn (mixed $id): int|string => $this->assertValidRelatedKeyShape($id, $relatedRepositoryKey),
            $requestedIds,
        );

        $model = $this->relatedModelPrototype();

        $resolved = $this->matchModelsToIds($this->relatedModelQuery($model), $ids);

        $unmatched = array_diff_key($ids, $resolved);

        if ($unmatched !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, array_values($unmatched));
        }

        $this->rememberRelatedModels($ids, $resolved, $model);

        ksort($resolved);

        return Collection::make(array_values($resolved));
    }
}
