<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Contracts\Deletable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\DeleteField;

trait DeletesFields
{
    protected function deleteFields(RestifyRequest $request, $model)
    {
        ($repository = $request->repositoryWith($model))
            ->collectFields($request)
            ->whereInstanceOf(Deletable::class)
            ->filter(fn (Field $field) => $field instanceof Deletable)
            ->filter(fn (Deletable $field) => $field->isPrunable())
            ->resolve($repository)
            ->each(function ($field) use ($request, $model) {
                DeleteField::forRequest($request, $field, $model)->save();
            });
    }
}
