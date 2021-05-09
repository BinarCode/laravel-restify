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

    public function relatedEagerField(): EagerField
    {
        $parentRepository = $this->repository(
            $this->route('parentRepository')
        );

        $parentRepository->withResource(
            $parentRepository::newModel()->newQuery()->whereKey(
                $this->route('parentRepositoryId')
            )->first()
        );

        /** * @var EagerField $eagerField */
        $eagerField = $parentRepository::collectRelated()
            ->forEager($this)
            ->first(fn($field, $key) => $key === $this->route('repository'));

        if (is_null($eagerField)) {
            abort(403, 'Eager field missing from the parent ['.$this->route('parentRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }
}
