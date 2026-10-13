<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Commands\Support\RestifyAuthCallLocator;
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

        if ((new RestifyAuthCallLocator)->has($content)) {
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
}
