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
     * @param RestifyRequest $request
     * @return Collection
     */
    abstract public function collectFields(RestifyRequest $request);

    /**
     * @return mixed
     */
    abstract public static function newModel();

    /**
     * @param RestifyRequest $request
     * @param array $plainPayload
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validatorForStoring(RestifyRequest $request, array $plainPayload = null)
    {
        /** * @var Repository $on */
        $on = static::resolveWith(static::newModel());

        $messages = $on->collectFields($request)->flatMap(function ($k) {
            $messages = [];
            foreach ($k->messages as $ruleFor => $message) {
                $messages[$k->attribute.'.'.$ruleFor] = $message;
            }

            return $messages;
        })->toArray();

        return Validator::make($plainPayload ?? $request->all(), $on->getStoringRules($request), $messages)->after(function ($validator) use ($request) {
            static::afterValidation($request, $validator);
            static::afterStoringValidation($request, $validator);
        });
    }

    public static function validatorForStoringBulk(RestifyRequest $request, array $plainPayload = null)
    {
        /** * @var Repository $on */
        $on = static::resolveWith(static::newModel());

        $messages = $on->collectFields($request)->flatMap(function ($k) {
            $messages = [];
            foreach ($k->messages as $ruleFor => $message) {
                $messages['*'.$k->attribute.'.'.$ruleFor] = $message;
            }

            return $messages;
        })->toArray();

        return Validator::make($plainPayload ?? $request->all(), $on->getStoringBulkRules($request), $messages)->after(function ($validator) use ($request) {
            static::afterValidation($request, $validator);
            static::afterStoringBulkValidation($request, $validator);
        });
    }

    /**
     * Validate a resource update request.
     * @param RestifyRequest $request
     * @param null $resource
     */
    public static function validateForUpdate(RestifyRequest $request, $resource = null)
    {
        static::validatorForUpdate($request, $resource)->validate();
    }

    /**
     * @param RestifyRequest $request
     * @param null $resource
     * @param array $plainPayload
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validatorForUpdate(RestifyRequest $request, $resource = null, array $plainPayload = null)
    {
        /** * @var Repository $on */
        $on = $resource ?? static::resolveWith(static::newModel());

        $messages = $on->collectFields($request)->flatMap(function ($k) {
            $messages = [];
            foreach ($k->messages as $ruleFor => $message) {
                $messages[$k->attribute.'.'.$ruleFor] = $message;
            }

            return $messages;
        })->toArray();

        return Validator::make($plainPayload ?? $request->all(), $on->getUpdatingRules($request), $messages)->after(function ($validator) use ($request) {
            static::afterValidation($request, $validator);
            static::afterUpdatingValidation($request, $validator);
        });
    }

    /**
     * Handle any post-validation processing.
     *
     * @param RestifyRequest $request
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected static function afterValidation(RestifyRequest $request, $validator)
    {
        //
    }

    /**
     * Handle any post-storing validation processing.
     *
     * @param RestifyRequest $request
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected static function afterStoringValidation(RestifyRequest $request, $validator)
    {
    }

    protected static function afterStoringBulkValidation(RestifyRequest $request, $validator)
    {
    }

    /**
     * Handle any post-storing validation processing.
     *
     * @param RestifyRequest $request
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected static function afterUpdatingValidation(RestifyRequest $request, $validator)
    {
    }

    /**
     * @param RestifyRequest $request
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

    public function getStoringBulkRules(RestifyRequest $request)
    {
        return $this->collectFields($request)->mapWithKeys(function (Field $k) {
            return [
                "*.{$k->attribute}" => $k->getStoringBulkRules(),
            ];
        })->toArray();
    }

    /**
     * @param RestifyRequest $request
     * @return array
     */
    public function getUpdatingRules(RestifyRequest $request)
    {
        return $this->collectFields($request)->mapWithKeys(function (Field $k) {
            return [
                $k->attribute => $k->getUpdatingRules(),
            ];
        })->toArray();
    }
}
