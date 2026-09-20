<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RepositorySyncRequest extends RestifyRequest
{
    public function syncRelatedModels(): Collection
    {
        $relatedRepository = $this->repository(
            Restify::repositoryForTable($table = $this->relatedRepository)::uriKey()
        );

        if (is_null($relatedRepository)) {
            abort(JsonResponse::HTTP_BAD_REQUEST, "Missing repository for the [{$table}] table");
        }

        return collect(Arr::wrap($this->input($this->relatedRepository)))
            ->map(fn ($id) => $relatedRepository->model()->newModelQuery()->whereKey($id)->first());
    }
}
