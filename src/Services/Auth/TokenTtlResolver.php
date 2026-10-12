<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Services\Auth;

use InvalidArgumentException;

final class TokenTtlResolver
{
    public static function seconds(mixed $configured): ?int
    {
        if ($configured === null || $configured === '') {
            return null;
        }

        if (is_numeric($configured)) {
            $minutes = (float) $configured;

            if (is_finite($minutes) && $minutes > 0) {
                $seconds = (int) round($minutes * 60);

                if ($seconds > 0) {
                    return $seconds;
                }
            }
        }

        $value = json_encode($configured) ?: get_debug_type($configured);

        throw new InvalidArgumentException(
            "Invalid restify.auth.token_ttl [{$value}]: expected a positive number of minutes that is at least one second, or null for tokens that never expire."
        );
    }
}
