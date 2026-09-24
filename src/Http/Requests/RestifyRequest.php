<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Requests\Concerns\DetermineRequestType;
use Binaryk\LaravelRestify\Http\Requests\Concerns\InteractWithRepositories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use JsonException;
use Throwable;

class RestifyRequest extends FormRequest
{
    use DetermineRequestType;
    use InteractWithRepositories;

    public function rules(): array
    {
        return [];
    }

    /**
     * The request body, without the query string or uploaded files merged in.
     *
     * @return array<array-key, mixed>
     */
    public function payload(): array
    {
        if ($this->isJson()) {
            return $this->json()->all();
        }

        return array_replace_recursive($this->post(), $this->allFiles());
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
            abort(JsonResponse::HTTP_FORBIDDEN, 'Eager field missing from the parent ['.$this->route('parentRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }

    public function pagination(): PaginationDataObject
    {
        $perPage = $this->input('page.size') ?? $this->input('perPage');

        $page = is_array($this->input('page')) ? $this->input('page.number') : $this->input('page');

        return PaginationDataObject::fromInput($perPage, $page);
    }

    public function related(): RelatedDto
    {
        try {
            return app(RelatedDto::class)->sync($this, currentRepository() ?? $this->repository());
        } catch (Throwable) {
            return app(RelatedDto::class);
        }
    }

    /**
     * @throws JsonException
     */
    public function filters(): array
    {
        $filters = $this->input('filters');

        if ($this instanceof RepositoryApplyFiltersRequest) {
            return $filters ?? [];
        }

        if ($filters === null) {
            return [];
        }

        return json_decode(base64_decode($filters), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    public function groupBy(): ?string
    {
        return $this->input('group_by');
    }
}
