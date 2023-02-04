<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Binaryk\LaravelRestify\Exceptions\Solutions\OpenAiSolution;
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

        if (! config('openai.api_key')) {
            return $response;
        }

        $solution = (new OpenAiSolution($e))->getSolutionDescription();

        return array_merge([
            'restify-solution' => $solution,
        ], $response);
    }
}
