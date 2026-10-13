<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait MatchesRelatedModels
{
    use CanonicalizesRelatedKeys;

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, Model> the models the database matched, keyed by the position of the id they match. An id the database did not return is absent.
     */
    private function matchModelsToIds(Model $model, array $ids, bool $applyGlobalScopes = false): array
    {
        $models = $this->relatedQuery($model, $applyGlobalScopes)->whereKey($ids)->get();

        $resolved = $this->matchRelatedModels($ids, $models, $model, caseInsensitive: false);

        $unmatched = array_diff_key($ids, $resolved);

        // A string key the database collation matches but PHP does not (e.g.
        // "ABC-UUID" against a stored "abc-uuid") gets one batched retry that lets
        // the database decide. An int key needs none: normalizeRelatedModelKey()
        // already mirrors how the database coerces it.
        if ($unmatched !== [] && ! $this->hasIntegerKey($model)) {
            $retried = $this->relatedQuery($model, $applyGlobalScopes)->whereKey(array_values($unmatched))->get();

            $resolved += $this->matchRelatedModels($unmatched, $retried, $model, caseInsensitive: true);
        }

        return $resolved;
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
        $key = $this->canonicalRelatedKey($key);

        if ($this->hasIntegerKey($model)) {
            return $this->canonicalIntegerKey($key);
        }

        return $caseInsensitive ? mb_strtolower((string) $key) : (string) $key;
    }

    private function canonicalIntegerKey(int|string $key): int|string
    {
        if (is_int($key)) {
            return $key;
        }

        $trimmed = trim($key);

        return preg_match('/^[+-]?\\d+$/', $trimmed) === 1 ? (int) $trimmed : $key;
    }

    /**
     * @return Builder<Model>
     */
    private function relatedQuery(Model $model, bool $applyGlobalScopes): Builder
    {
        return $applyGlobalScopes ? $model->newQuery() : $model->newModelQuery();
    }

    private function hasIntegerKey(Model $model): bool
    {
        return in_array($model->getKeyType(), ['int', 'integer'], true);
    }
}
