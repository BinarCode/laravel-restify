<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedResetUrlHost implements ValidationRule
{
    /**
     * Backslash, whitespace, and control characters: a browser normalizes
     * these differently in a URL's authority than parse_url does, which is
     * how `https://evil.com\@good.com/` parses to host `good.com` here while
     * a browser navigates to `evil.com`.
     */
    private const FORBIDDEN_CHARACTERS = '/[\\\\\s\x00-\x1f\x7f]/';

    /**
     * @param  list<string>  $allowedOrigins  Lowercased "scheme://host[:port]" values the url is allowed to target.
     */
    public function __construct(
        private readonly array $allowedOrigins,
    ) {}

    public static function fromConfig(): self
    {
        /** @var string|null $passwordResetUrl */
        $passwordResetUrl = config('restify.auth.password_reset_url');

        /** @var string|null $appUrl */
        $appUrl = config('app.url');

        $candidates = [
            self::hostOf($passwordResetUrl),
            self::hostOf($appUrl),
        ];

        if (config()->has('restify.auth.frontend_app_url')) {
            /** @var string|null $frontendAppUrl */
            $frontendAppUrl = config('restify.auth.frontend_app_url');

            $candidates[] = self::hostOf($frontendAppUrl);
        }

        return new self(array_values(array_unique(array_filter($candidates))));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $origin = is_string($value) ? self::hostOf($value) : null;

        if ($origin === null || ! in_array($origin, $this->allowedOrigins, true)) {
            $fail('The :attribute host is not allowed.')->translate();
        }
    }

    /**
     * Extracts a lowercased "scheme://host[:port]" from a url, or null when
     * the url is malformed, carries userinfo, uses a disallowed scheme, or
     * contains a forbidden character.
     */
    private static function hostOf(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (preg_match(self::FORBIDDEN_CHARACTERS, $url) === 1) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $origin = $scheme.'://'.strtolower($parts['host']);

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
