<?php

namespace Binaryk\LaravelRestify\Filters;

use Illuminate\Support\Collection;

class RelatedQueryCollection extends Collection
{
    public static function fromString(string $related): RelatedQuery
    {
        $parent = static::fromToken(str($related)->before('.')->toString());

        if (! str($related)->contains('.')) {
            return $parent;
        }

        collect(str($related)->after('.')->explode('.'))
            ->map(function (string $nested, $i) use ($parent) {
                if ($i === 0) {
                    return $parent->nested->push(static::fromToken($nested));
                }

                return $parent->nested->nth($i)->first()->nested->push(static::fromToken($nested));
            });

        return $parent;
    }

    public static function fromToken(string $token): RelatedQuery
    {
        if (str($token)->contains('[')) {
            // has columns
            return new RelatedQuery(
                relation: str($token)->before('['),
                columns: str($token)->between('[', ']')->explode('|')->all(),
            );
        }

        if (str($token)->contains('[')) {
            // has columns
            return new RelatedQuery(
                relation: str($token)->before('['),
                columns: str($token)->between('[', ']')->explode('|')->all(),
            );
        }

        return new RelatedQuery($token);
    }
}
