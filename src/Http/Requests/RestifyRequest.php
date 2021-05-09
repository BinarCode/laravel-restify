<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\Concerns\DetermineRequestType;
use Binaryk\LaravelRestify\Http\Requests\Concerns\InteractWithRepositories;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Foundation\Http\FormRequest;

class RestifyRequest extends FormRequest
{
    use DetermineRequestType;
    use InteractWithRepositories;

    public function rules(): array
    {
        return [];
    }

    public function isViaRepository(): bool
    {
        $viaRepository = $this->route('viaRepository');
        $viaRepositoryId = $this->route('viaRepositoryId');

        //TODO: Find another implementation for prefixes:
        $matchSomePrefixes = collect(Restify::$repositories)
                ->some(fn($repository) => $repository::prefix() === "$viaRepository/$viaRepositoryId")
            || collect(Restify::$repositories)->some(fn(
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
            ->first(fn($field, $key) => $key === $this->route('repository'));

        if (is_null($eagerField)) {
            abort(403, 'Eager field missing from the parent ['.$this->route('viaRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }
}
