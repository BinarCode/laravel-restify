<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\PaginationDto;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Requests\Concerns\DetermineRequestType;
use Binaryk\LaravelRestify\Http\Requests\Concerns\InteractWithRepositories;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

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
            ->first(fn (EagerField $field, $key) => $field->getAttribute() === $this->route('repository'));

        if (is_null($eagerField)) {
            abort(403, 'Eager field missing from the parent ['.$this->route('parentRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }

    public function pagination(): PaginationDto
    {
        $perPage = ($this->input('page.size') ?? $this->input('perPage'));

        if (is_array($this->input('page'))) {
            $pageNumber = $this->input('page.number');
        } else {
            $pageNumber = $this->input('page');
        }

        return new PaginationDto(
            perPage: $perPage,
            page: $pageNumber,
        );
    }

    public function related(): RelatedDto
    {
        try {
            return app(RelatedDto::class)->sync($this, currentRepository() ?? $this->repository());
        } catch (Throwable) {
            return app(RelatedDto::class);
        }
    }
}
