<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Requests\Concerns\DetermineRequestType;
use Binaryk\LaravelRestify\Http\Requests\Concerns\InteractWithRepositories;
use Binaryk\LaravelRestify\Http\Requests\Concerns\ResolvesRelatedModels;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Throwable;

class RestifyRequest extends FormRequest
{
    use DetermineRequestType;
    use InteractWithRepositories;
    use ResolvesRelatedModels;

    public function rules(): array
    {
        return [];
    }

    /**
     * Run the callback with the body narrowed to one bulk row, still keyed by its index, so the
     * row's fields, model events and actions read that row's input only. The query string is kept
     * and the whole body is restored afterwards, even when the callback throws.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function forBulkRow(int $row, Closure $callback): mixed
    {
        $json = $this->json;
        $body = $this->request;
        $files = $this->files;
        $convertedFiles = $this->convertedFiles;

        $key = (string) $row;
        $input = $this->isJson() ? $this->json() : $body;

        $this->convertedFiles = array_key_exists($row, $all = $this->allFiles()) ? [$row => $all[$row]] : [];
        $this->files = new FileBag;
        $this->request = new InputBag;

        if ($files->has($key)) {
            $this->files->set($key, $files->all($key));
        }

        if ($input->has($key)) {
            $this->request->set($key, $input->all($key));
        }

        if ($this->isJson()) {
            $this->json = $this->request;
        }

        try {
            return $callback();
        } finally {
            $this->json = $json;
            $this->request = $body;
            $this->files = $files;
            $this->convertedFiles = $convertedFiles;
        }
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

    /**
     * The `relatedRepository` URL segment (e.g. `users` in `attach/users`).
     *
     * Reads the route parameter directly, never the magic `__get`/`input()`
     * resolution, so a body key of the same name can't shadow it.
     *
     * @throws InvalidArgumentException if the route parameter is not a string.
     */
    public function relatedRepositoryKey(): string
    {
        $relatedRepository = $this->route('relatedRepository');

        if (! is_string($relatedRepository)) {
            throw new InvalidArgumentException('The [relatedRepository] route parameter must be a string.');
        }

        return $relatedRepository;
    }

    /**
     * The `repositoryId` URL segment.
     *
     * Reads the route parameter directly, never the magic `__get`/`input()`
     * resolution, so a body key of the same name can't shadow it.
     *
     * @throws InvalidArgumentException if the route parameter is not a string.
     */
    public function repositoryIdFromRoute(): string
    {
        $repositoryId = $this->route('repositoryId');

        if (! is_string($repositoryId)) {
            throw new InvalidArgumentException('The [repositoryId] route parameter must be a string.');
        }

        return $repositoryId;
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
