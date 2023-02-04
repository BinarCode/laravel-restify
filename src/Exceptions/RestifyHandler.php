<?php

namespace Binaryk\LaravelRestify\Exceptions;

use App\Solutions\OpenAiSolutionProvider;
use Binaryk\LaravelRestify\Exceptions\Solutions\OpenAiSolution;
use Illuminate\Foundation\Exceptions\Handler;
use Throwable;

class RestifyHandler extends Handler
{
    public function getDontFlash(): array
    {
        return [
            'password',
            'password_confirmation',
        ];
    }

    protected function convertExceptionToArray(Throwable $e): array
    {
        $response = parent::convertExceptionToArray($e);

        if (!config('app.debug')) {
            return $response;
        }

        if (!config('openai.api_key')) {
            return $response;
        }

        if (!config('restify.ai_solutions')) {
            return $response;
        }

        $solution = (new OpenAiSolution($e))->getSolutionDescription();

        return array_merge([
            'restify-solution' => $solution,
        ], $response);
    }
}
