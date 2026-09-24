<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Traits;

use Illuminate\Validation\ValidationException;

trait ValidatesRelatedKeyShape
{
    /**
     * @return int|string the given id, unchanged.
     *
     * @throws ValidationException if the given id is neither an int nor a string.
     */
    private function assertValidRelatedKeyShape(mixed $id, string $inputName): int|string
    {
        if (! is_int($id) && ! is_string($id)) {
            throw ValidationException::withMessages([
                $inputName => [__('Each attached id must be an int or a string.')],
            ]);
        }

        return $id;
    }
}
