<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class RestifyJsSetupController extends Controller
{
    public function __invoke(RestifyRequest $request)
    {
        if (App::environment('production')) {
            $this->authorize($request);
        }

        return response()->json([
            'config' => $this->config(),
            'repositories' => $this->repositories($request),
        ]);
    }

    private function repositories(RestifyRequest $request): array
    {
        return collect(Restify::$repositories)
            ->map(fn (string $repository) => app($repository))
            ->map(fn (Repository $repository) => $repository->restifyjsSerialize($request))
            ->all();
    }

    private function config(): array
    {
        return [
            'domain' => $this->deleteFirstAndLastSlash(config('restify.restifyjs.api_url') ?? ''),
            'base' => $this->deleteFirstAndLastSlash(Restify::path()),
        ];
    }

    private function deleteFirstAndLastSlash(string $domain): string
    {
        if (Str::startsWith($domain, '/')) {
            $domain = Str::replaceFirst('/', '', $domain);
        }

        if (Str::endsWith($domain, '/')) {
            $domain = Str::replaceLast('/', '', $domain);
        }

        return $domain;
    }

    private function authorize(Request $request)
    {
        if ($request->input('token') !== config('restify.restifyjs.token')) {
            abort(401, 'You are not authorized to see this request.');
        }
    }
}
