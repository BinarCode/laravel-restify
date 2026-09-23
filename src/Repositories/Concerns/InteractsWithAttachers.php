<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Str;

trait InteractsWithAttachers
{
    public function belongsToManyField(RestifyRequest $request): ?BelongsToMany
    {
        return $request->repository()::collectRelated()
            ->forManyToManyRelations($request)
            ->firstWhere('attribute', $request->route('relatedRepository'));
    }

    public function authorizeBelongsToMany(RestifyRequest $request): self
    {
        if (is_null($field = $this->belongsToManyField($request))) {
            $class = class_basename($request->repository());
            abort(400, "Missing BelongsToMany or MorphToMany related for [{$request->relatedRepository}]. This relationship should be in the related of the [{$class}] class. Or you are not authorized to use that repository (see `allowRestify` policy method).");
        }

        return $this;
    }

    public function guessAttachMethod(RestifyRequest $request): ?callable
    {
        if (is_callable($method = $this->belongsToManyField($request)->attachCallback)) {
            return $method;
        }

        $repository = $request->repository();

        $key = $request->relatedRepository;

        if (array_key_exists($key, $repository::getAttachers()) && is_callable($cb = $repository::getAttachers()[$key])) {
            return $cb;
        }

        $methodGuesser = 'attach'.Str::studly($request->relatedRepository);

        if (method_exists($repository, $methodGuesser)) {
            return [$repository, $methodGuesser];
        }

        return null;
    }

    public function guessDetachMethod(RestifyRequest $request): ?callable
    {
        if (is_callable($method = $this->belongsToManyField($request)->detachCallback)) {
            return $method;
        }

        $repository = $request->repository();

        $key = $request->relatedRepository;

        if (array_key_exists($key, $repository::getDetachers()) && is_callable($cb = $repository::getDetachers()[$key])) {
            return $cb;
        }

        $methodGuesser = 'detach'.Str::studly($request->relatedRepository);

        if (method_exists($repository, $methodGuesser)) {
            return [$repository, $methodGuesser];
        }

        return null;
    }
}
