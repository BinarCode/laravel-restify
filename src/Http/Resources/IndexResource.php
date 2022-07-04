<?php

namespace Binaryk\LaravelRestify\Http\Resources;

use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;

class IndexResource extends Resource
{
    public function toArray($request): array
    {
        $resource = $this->authorizedResourceForRequest($request);

        [$paginator, $total, $sortable] = $request->searchIndex();

        return [];
    }

    public function authorizedResourceForRequest(RepositoryIndexRequest $request)
    {
        return tap($request->repository(), function ($resource) use ($request) {
            abort_unless($resource::authorizedToViewAny($request), 403);
        });
    }
}
