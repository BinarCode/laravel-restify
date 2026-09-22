<?php

namespace Binaryk\LaravelRestify\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedResetUrlHost implements ValidationRule
{
    /**
     * @param  list<string>  $allowedHosts  Lowercased hostnames the url is allowed to point at.
     */
    public function __construct(
        private readonly array $allowedHosts,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = is_string($value) ? self::hostOf($value) : null;

        if ($host === null || ! in_array($host, $this->allowedHosts, true)) {
            $fail('The :attribute host is not allowed.');
        }
    }

    public static function hostOf(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        return strtolower($parts['host']);
    }
}
