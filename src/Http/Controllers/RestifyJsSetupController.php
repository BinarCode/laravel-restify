<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RestifyJsSetupController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'config' => $this->config(),
            'repositories' => $this->repositories(),
        ]);
    }

    private function repositories(): array
    {
        return collect(Restify::$repositories)
            ->map(fn (string $repository) => app($repository))
            ->map(fn (Repository $repository) => $repository->restifyjsSerialize())
            ->all();
    }

    private function config(): array
    {
        return [
            'domain' => config('app.url'),
            'base' => Restify::path(),
        ];
    }
}
