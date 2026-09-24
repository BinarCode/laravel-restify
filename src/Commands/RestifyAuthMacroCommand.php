<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpToken;

class RestifyAuthMacroCommand extends Command
{
    protected $signature = 'restify:auth-macro';

    protected $description = 'Adding Restify Auth routes to routes/api.php';

    public function handle(): int
    {
        $routesPath = base_path('routes/api.php');

        if (! File::exists($routesPath)) {
            $this->error('The routes/api.php file does not exist. If you are on Laravel 11 or higher, run `php artisan install:api` first.');

            return self::FAILURE;
        }

        $content = File::get($routesPath);
        $restifyAuthRoute = 'Route::restifyAuth();';

        if ($this->hasRestifyAuthCall($content)) {
            $this->info('The restifyAuth route is already in the routes/api.php file.');

            return self::SUCCESS;
        }

        if ($this->endsWithCloseTag($content)) {
            $this->error('routes/api.php ends with a closing ?> tag. Remove it so this command can safely append the restifyAuth route.');

            return self::FAILURE;
        }

        $content .= "\n".$restifyAuthRoute."\n";

        File::put($routesPath, $content);
        $this->info('The restifyAuth route has been appended to the routes/api.php file.');

        return self::SUCCESS;
    }

    /**
     * Detects a real `Route::restifyAuth(...)` call by walking the file's
     * tokens for `Route` (or the fully-qualified `\Route`), `::`,
     * `restifyAuth` and an opening `(`. A call built only from that exact
     * token sequence is never mistaken for text sitting inside a comment,
     * a string, or a heredoc - unlike the plain `str_contains` this replaces,
     * which treated a commented-out `// Route::restifyAuth();` as present.
     *
     * Duplicates the token-walk approach in `PublishAuthCommand::locateRestifyAuthCall()`
     * (PR #769) rather than sharing it, so the two PRs stay decoupled; worth
     * extracting into a shared helper once both have merged.
     */
    private function hasRestifyAuthCall(string $content): bool
    {
        $tokens = PhpToken::tokenize($content);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            $isRouteToken = ($token->is(T_STRING) && $token->text === 'Route')
                || ($token->is(T_NAME_FULLY_QUALIFIED) && $token->text === '\Route');

            if (! $isRouteToken) {
                continue;
            }

            $cursor = $this->skipInsignificantTokens($tokens, $count, $i + 1);

            if ($cursor === null || ! $tokens[$cursor]->is(T_DOUBLE_COLON)) {
                continue;
            }

            $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

            if ($cursor === null || ! $tokens[$cursor]->is(T_STRING) || $tokens[$cursor]->text !== 'restifyAuth') {
                continue;
            }

            $cursor = $this->skipInsignificantTokens($tokens, $count, $cursor + 1);

            if ($cursor !== null && $tokens[$cursor]->text === '(') {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the last substantive token in the file is a closing `?>` tag,
     * meaning anything appended afterwards would land outside PHP and render
     * as inline HTML instead of being executed.
     */
    private function endsWithCloseTag(string $content): bool
    {
        $tokens = PhpToken::tokenize($content);

        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if ($token->is(T_WHITESPACE) || ($token->is(T_INLINE_HTML) && trim($token->text) === '')) {
                continue;
            }

            return $token->is(T_CLOSE_TAG);
        }

        return false;
    }

    /**
     * @param  array<PhpToken>  $tokens
     */
    private function skipInsignificantTokens(array $tokens, int $count, int $from): ?int
    {
        for ($i = $from; $i < $count; $i++) {
            if (! $tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return $i;
            }
        }

        return null;
    }
}
