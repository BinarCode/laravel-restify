<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait ValidatingTrait
{
    /**
     * @param  RestifyRequest  $request
     * @return Collection
     */
    abstract public function collectFields(RestifyRequest $request);

    /**
     * @return mixed
     */
    abstract public static function newModel();

    /**
     * @param  RestifyRequest  $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validatorForStoring(RestifyRequest $request)
    {
        $on = (new static(static::newModel()));

        $messages = $on->collectFields($request)->flatMap(function ($k) {
            $messages = [];
            foreach ($k->messages as $ruleFor => $message) {
                $messages[$k->attribute.'.'.$ruleFor] = $message;
            }

            return $messages;
        })->toArray();

        return Validator::make($request->all(), $on->getStoringRules($request), $messages)->after(function ($validator) use ($request) {
            static::afterValidation($request, $validator);
            static::afterStoringValidation($request, $validator);
        });
    }

    /**
     * Handle any post-validation processing.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected static function afterValidation(RestifyRequest $request, $validator)
    {
        //
    }

    /**
     * Handle any post-storing validation processing.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected static function afterStoringValidation(RestifyRequest $request, $validator)
    {
    }

    /**
     * @param  RestifyRequest  $request
     * @return array
     */
    public function getStoringRules(RestifyRequest $request)
    {
        return $this->collectFields($request)->mapWithKeys(function (Field $k) {
            return [
                $k->attribute => $k->getStoringRules(),
            ];
        })->toArray();
    }
}
