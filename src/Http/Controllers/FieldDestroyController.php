<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Contracts\Deletable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\DeleteField;
use Illuminate\Routing\Controller;

class FieldDestroyController extends Controller
{
    public function __invoke(RestifyRequest $request)
    {
        $repository = $request->repositoryWith(
            $model = $request->modelQuery()->firstOrFail()
        );

        $repository->authorizeToUpdate($request);

        $field = $repository->collectFields($request)
            ->whereInstanceOf(Deletable::class)
            ->filter(fn (Deletable $field) => $field->isDeletable())
            ->resolve($repository)
            ->findFieldByAttribute($request->field, function () {
                abort(404);
            });

        if (is_null($field)) {
            abort(404);
        }

        DeleteField::forRequest(
            $request,
            $field,
            $repository->resource
        )->save();

        return response()->noContent();
    }
}
