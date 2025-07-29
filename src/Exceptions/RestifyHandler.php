<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Binaryk\LaravelRestify\Exceptions\Solutions\AiSolution;
use Illuminate\Foundation\Exceptions\Handler;
use Throwable;

class RestifyHandler extends Handler
{
    protected function convertExceptionToArray(Throwable $e): array
    {
        $response = parent::convertExceptionToArray($e);

        if (! config('restify.ai_solutions')) {
            return $response;
        }

        if (! config('app.debug')) {
            return $response;
        }

        if (! config('prism.prism.api_key')) {
            return $response;
        }

        $solution = (new AiSolution($e))->getSolutionDescription();

        return array_merge([
            'restify-solution' => $solution,
        ], $response);
    }
}
