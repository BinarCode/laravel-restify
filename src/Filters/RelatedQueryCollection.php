<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class RelatedQueryCollection extends Collection
{
    public function forRepository(RestifyRequest $request, Repository $repository): self
    {
        $repositoryRelated = $repository::collectRelated()
            ->intoAssoc()
            ->mapIntoRelated($request, $repository);

        return $this->filter(fn(RelatedQuery $relatedQuery) => $repositoryRelated->contains('relation', $relatedQuery->relation));
    }
}
