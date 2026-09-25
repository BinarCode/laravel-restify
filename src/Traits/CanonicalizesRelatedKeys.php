<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Traits;

use BackedEnum;
use InvalidArgumentException;
use Stringable;

trait CanonicalizesRelatedKeys
{
    private function canonicalRelatedKey(mixed $key): int|string
    {
        if ($key instanceof BackedEnum) {
            return $key->value;
        }

        if ($key instanceof Stringable) {
            return (string) $key;
        }

        if (is_int($key) || is_string($key)) {
            return $key;
        }

        throw new InvalidArgumentException('The related model key must be an int, a string, a BackedEnum, or a Stringable.');
    }
}
