<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        if (str_contains($content, 'Route::restifyAuth(')) {
            $this->info('The restifyAuth route is already in the routes/api.php file.');

            return self::SUCCESS;
        }

        $content .= "\n".$restifyAuthRoute."\n";

        File::put($routesPath, $content);
        $this->info('The restifyAuth route has been appended to the routes/api.php file.');

        return self::SUCCESS;
    }
}
