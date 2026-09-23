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
     * RFC 3986 unreserved and reserved characters allowed in a path, query,
     * or fragment - deliberately narrower than RFC 3986 itself, since it
     * excludes characters ("(" ")" "'" "<" ">" "\"" "`" "{" "}" "[" "]")
     * that also double as Markdown or HTML syntax. The mailed reset link is
     * rendered through a Markdown template (`[url]($actionUrl)`), and any of
     * those characters in the url could close that link early and open a
     * second, attacker-controlled one carrying the real token.
     */
    private const ALLOWED_PATH_CHARACTERS = '/^[A-Za-z0-9\-._~!$&*+,;=:@\/?#%]*$/';

    /**
     * Literal placeholders the caller may still use in the path, query, or
     * fragment despite the curly braces the character allow-list otherwise
     * rejects - they are substituted with rawurlencode()-d values afterward.
     */
    private const PLACEHOLDERS = ['{token}', '{email}'];

    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

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

        /** @var string|null $frontendAppUrl */
        $frontendAppUrl = config('restify.auth.frontend_app_url');

        return new self(array_values(array_unique(array_filter([
            self::originOf($passwordResetUrl),
            self::originOf($appUrl),
            self::originOf($frontendAppUrl),
        ]))));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $origin = is_string($value) ? self::originOf($value) : null;

        if ($origin === null || ! in_array($origin, $this->allowedOrigins, true)) {
            $fail('The :attribute host is not allowed.')->translate();
        }
    }

    private static function originOf(?string $url): ?string
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

        if (! self::pathQueryAndFragmentAreSafe($parts)) {
            return null;
        }

        $origin = $scheme.'://'.strtolower($parts['host']);

        if (isset($parts['port']) && $parts['port'] !== self::DEFAULT_PORTS[$scheme]) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }

    /**
     * @param  array<string, int|string>  $parts  A parse_url() result.
     */
    private static function pathQueryAndFragmentAreSafe(array $parts): bool
    {
        $remainder = ($parts['path'] ?? '')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

        $remainder = str_replace(self::PLACEHOLDERS, '', (string) $remainder);

        return preg_match(self::ALLOWED_PATH_CHARACTERS, $remainder) === 1;
    }
}
