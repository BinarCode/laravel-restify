<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Commands\Support;

use PhpToken;

final class RestifyAuthCallLocator
{
    /**
     * Whether the file holds a real `Route::restifyAuth(` call. A call is built
     * only from the exact token sequence `Route` (or the fully-qualified
     * `\Route`), `::`, `restifyAuth` and `(`, so text inside a comment, a
     * string or a heredoc is never mistaken for it.
     */
    public function has(string $contents): bool
    {
        $tokens = PhpToken::tokenize($contents);
        $count = count($tokens);

        for ($routeIndex = 0; $routeIndex < $count; $routeIndex++) {
            if ($this->openingParenthesisIndex($tokens, $count, $routeIndex) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every complete `Route::restifyAuth(...);` statement: balanced parentheses
     * followed by a `;`. Offsets are byte positions in `$contents`.
     *
     * @return list<array{offset: int, length: int, arguments: string}>
     */
    public function locate(string $contents): array
    {
        $tokens = PhpToken::tokenize($contents);
        $count = count($tokens);
        $calls = [];

        for ($routeIndex = 0; $routeIndex < $count; $routeIndex++) {
            $openIndex = $this->openingParenthesisIndex($tokens, $count, $routeIndex);

            if ($openIndex === null) {
                continue;
            }

            $call = $this->completeCall($tokens, $count, $routeIndex, $openIndex, $contents);

            if ($call !== null) {
                $calls[] = $call;
            }
        }

        return $calls;
    }

    /**
     * Byte position of the last `?>` tag in the file, if any.
     */
    public function lastCloseTagOffset(string $contents): ?int
    {
        $offset = null;

        foreach (PhpToken::tokenize($contents) as $token) {
            if ($token->is(T_CLOSE_TAG)) {
                $offset = $token->pos;
            }
        }

        return $offset;
    }

    /**
     * @param  array<PhpToken>  $tokens
     */
    private function openingParenthesisIndex(array $tokens, int $count, int $routeIndex): ?int
    {
        $token = $tokens[$routeIndex];

        $isRouteToken = ($token->is(T_STRING) && $token->text === 'Route')
            || ($token->is(T_NAME_FULLY_QUALIFIED) && $token->text === '\Route');

        if (! $isRouteToken) {
            return null;
        }

        $cursor = $this->skipInsignificantTokens($tokens, $count, $routeIndex + 1);

        if ($cursor === null || ! $tokens[$cursor]->is(T_DOUBLE_COLON)) {
            return null;
        }

        $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

        if ($cursor === null || ! $tokens[$cursor]->is(T_STRING) || $tokens[$cursor]->text !== 'restifyAuth') {
            return null;
        }

        $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

        if ($cursor === null || $tokens[$cursor]->text !== '(') {
            return null;
        }

        return $cursor;
    }

    /**
     * @param  array<PhpToken>  $tokens
     * @return array{offset: int, length: int, arguments: string}|null
     */
    private function completeCall(array $tokens, int $count, int $routeIndex, int $openIndex, string $contents): ?array
    {
        $argumentsStart = $tokens[$openIndex]->pos + 1;
        $depth = 1;
        $closeParenIndex = null;

        for ($index = $openIndex + 1; $index < $count; $index++) {
            $text = $tokens[$index]->text;

            if ($text === '(') {
                $depth++;
            } elseif ($text === ')') {
                $depth--;

                if ($depth === 0) {
                    $closeParenIndex = $index;

                    break;
                }
            }
        }

        if ($closeParenIndex === null) {
            return null;
        }

        $semicolonIndex = $this->skipInsignificantTokens($tokens, $count, $closeParenIndex + 1);

        if ($semicolonIndex === null || $tokens[$semicolonIndex]->text !== ';') {
            return null;
        }

        $offset = $tokens[$routeIndex]->pos;
        $argumentsEnd = $tokens[$closeParenIndex]->pos;

        return [
            'offset' => $offset,
            'length' => $tokens[$semicolonIndex]->pos + 1 - $offset,
            'arguments' => substr($contents, $argumentsStart, $argumentsEnd - $argumentsStart),
        ];
    }

    /**
     * @param  array<PhpToken>  $tokens
     */
    private function skipInsignificantTokens(array $tokens, int $count, int $from): ?int
    {
        for ($index = $from; $index < $count; $index++) {
            if (! $tokens[$index]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $index;
            }
        }

        return null;
    }
}
