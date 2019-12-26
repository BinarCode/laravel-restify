<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait RepositoryFillFields
{
    /**
     * Fill fields on store request.
     *
     * @param  RestifyRequest  $request
     * @param $model
     * @return array
     */
    public static function fillWhenStore(RestifyRequest $request, $model)
    {
        return [$model, static::fillFields(
            $request, $model,
            (new static($model))->collectFields($request)
        ), static::fillExtra($request, $model,
            (new static($model))->collectFields($request)
        )];
    }

    /**
     * @param  RestifyRequest  $request
     * @param $model
     * @return array
     */
    public static function fillWhenUpdate(RestifyRequest $request, $model)
    {
        return [$model, static::fillFields(
            $request, $model,
            (new static($model))->collectFields($request)
        ), static::fillExtra($request, $model,
            (new static($model))->collectFields($request)
        )];
    }

    /**
     * Fill each field separately.
     *
     * @param  RestifyRequest  $request
     * @param  Model  $model
     * @param  Collection  $fields
     * @return Model
     */
    protected static function fillFields(RestifyRequest $request, Model $model, Collection $fields)
    {
        $fields->map(function (Field $field) use ($request, $model) {
            return $field->fillAttribute($request, $model);
        })->values()->all();

        return $model;
    }

    /**
     * If some fields were not defined in the @fields method, but they are in fillable attributes and present in request,
     * they should be also filled on request.
     * @param  RestifyRequest  $request
     * @param  Model  $model
     * @param  Collection  $fields
     * @return array
     */
    protected static function fillExtra(RestifyRequest $request, Model $model, Collection $fields)
    {
        $definedAttributes = $fields->map->getAttribute()->toArray();
        $fromRequest = collect($request->only($model->getFillable()))->keys()->filter(function ($attribute) use ($definedAttributes) {
            return ! in_array($attribute, $definedAttributes);
        });

        return $fromRequest->each(function ($attribute) use ($request, $model) {
            $model->{$attribute} = $request->{$attribute};
        })->values()->all();
    }
}
