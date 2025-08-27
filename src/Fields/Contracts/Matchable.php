<?php

namespace Binaryk\LaravelRestify\Fields\Contracts;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

interface Matchable
{
    public function matchable(mixed $column = null, ?string $type = null): self;

    public function isMatchable(RestifyRequest $request = null): bool;

    public function getMatchColumn(RestifyRequest $request = null): mixed;

    public function getMatchType(RestifyRequest $request = null): ?string;
}
