<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\Deletable;
use Binaryk\LaravelRestify\Contracts\FileStorable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;

class DeleteField
{
    public static function forRequest(RestifyRequest $request, $field, $model): Model
    {
        $arguments = [
            $request,
            $model,
        ];

        if ($field instanceof FileStorable) {
            array_push($arguments, $field->getStorageDisk(), $field->getStoragePath());
        }

        /**
         * @var Deletable|Field $field
         */
        if (! is_callable($callback = $field->getDeleteCallback())) {
            return $model;
        }

        $result = call_user_func_array($callback, $arguments);

        if ($result === true) {
            return $model;
        }

        if (! is_array($result)) {
            $model->{$field->getAttribute()} = $result;
        } else {
            foreach ($result as $key => $value) {
                if ($model->isFillable($key)) {
                    $model->{$key} = $value;
                }
            }
        }

        return $model;
    }
}
