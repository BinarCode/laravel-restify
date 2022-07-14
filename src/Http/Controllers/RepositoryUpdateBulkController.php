<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    private array $repositories = [];

    public function __invoke(RepositoryUpdateBulkRequest $request)
    {
        $collection = DB::transaction(function () use ($request) {
            return $request->collectInput()
                ->each(function (array $item, int $row) use ($request) {
                    $model = $request->modelQuery(
                        $id = $item['id']
                    )->lockForUpdate()->firstOrFail();

                    /** * @var Repository $repository */
                    $repository = $request->repositoryWith($model);

                    if (! in_array($repository, $this->repositories)) {
                        $this->repositories[] = $repository;
                    }

                    return $repository
                        ->allowToUpdateBulk($request)
                        ->updateBulk(
                            $request,
                            $id,
                            $row
                        );
                });
        });

        /** @var Repository $repository */
        foreach ($this->repositories as $repository) {
            $repository::savedBulk($collection, $request);
            $repository::updatedBulk($collection, $request);
        }

        return $this->response()
            ->success();
    }
}
