<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\AiInstructions\RepositoryAiInstructionsRenderer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RepositoryAiInstructionsController extends RepositoryController
{
    public function __invoke(RestifyRequest $request, RepositoryAiInstructionsRenderer $renderer): JsonResponse
    {
        $repository = $request->repository();

        try {
            $instructions = $renderer($repository::uriKey());
        } catch (AuthorizationException $e) {
            throw new HttpException(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(404, $e->getMessage());
        }

        return response()->json([
            'repository' => $repository::uriKey(),
            'instructions' => $instructions,
        ]);
    }
}
