<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait InteractWithFields
{
    /**
     * Resolvable attributes.
     */
    public function fields(RestifyRequest $request): array
    {
        return [];
    }

    public function fieldsForIndex(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForShow(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForUpdate(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForStore(RestifyRequest $request): array
    {
        return $this->fields($request);
    }
}
