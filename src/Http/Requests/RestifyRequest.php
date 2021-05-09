<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class RestifyRequest extends FormRequest
{
    use InteractWithRepositories;

    public function isProduction(): bool
    {
        return App::environment('production');
    }

    /**
     * @return bool
     */
    public function isDev(): bool
    {
        return false === $this->isProduction();
    }

    /**
     * Determine if the request is on repository index e.g. restify-api/users.
     *
     * @return bool
     */
    public function isForRepositoryRequest(): bool
    {
        return $this instanceof RepositoryIndexRequest;
    }

    /**
     * Determine if the request is on repository detail e.g. restify-api/users/1
     * This will match any verbs (PATCH, DELETE or GET).
     * @return bool
     */
    public function isShowRequest(): bool
    {
        return $this instanceof RepositoryShowRequest;
    }

    public function isUpdateRequest(): bool
    {
        return $this instanceof RepositoryUpdateRequest;
    }

    public function isStoreRequest(): bool
    {
        return $this instanceof RepositoryStoreRequest;
    }

    public function isStoreBulkRequest(): bool
    {
        return $this instanceof RepositoryStoreBulkRequest;
    }

    public function isUpdateBulkRequest(): bool
    {
        return $this instanceof RepositoryUpdateBulkRequest;
    }

    public function isViaRepository(): bool
    {
        $viaRepository = $this->route('viaRepository');
        $viaRepositoryId = $this->route('viaRepositoryId');

        //TODO: Find another implementation for prefixes:
        $matchSomePrefixes = collect(Restify::$repositories)
                ->some(fn ($repository) => $repository::prefix() === "$viaRepository/$viaRepositoryId")
            || collect(Restify::$repositories)->some(fn (
                $repository
            ) => $repository::indexPrefix() === "$viaRepository/$viaRepositoryId");

        if ($matchSomePrefixes) {
            return false;
        }

        return $viaRepository && $viaRepositoryId;
    }

    public function relatedEagerField(): EagerField
    {
        $parentRepository = $this->repository(
            $this->route('viaRepository')
        );

        $parentRepository->withResource(
            $parentRepository::newModel()->newQuery()->whereKey(
                $this->route('viaRepositoryId')
            )->first()
        );

        /** * @var EagerField $eagerField */
        $eagerField = $parentRepository::collectRelated()
            ->forEager($this)
            ->first(fn ($field, $key) => $key === $this->route('repository'));

        if (is_null($eagerField)) {
            abort(403, 'Eager field missing from the parent ['.$this->route('viaRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }
}
