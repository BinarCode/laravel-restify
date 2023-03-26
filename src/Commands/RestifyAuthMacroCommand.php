<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestifyAuthMacroCommand extends Command
{
    protected $signature = 'restify:auth-macro';

    protected $description = 'Adding Restify Auth routes to routes/api.php';

    public function handle()
    {
        $routesPath = base_path('routes/api.php');

        if (! File::exists($routesPath)) {
            $this->error('The routes/api.php file does not exist.');

            return 1;
        }

        $content = File::get($routesPath);
        $restifyAuthRoute = 'Route::restifyAuth();';

        if (strpos($content, $restifyAuthRoute) !== false) {
            $this->info('The restifyAuth route is already in the routes/api.php file.');

            return 0;
        }

        $content .= "\n".$restifyAuthRoute."\n";

        File::put($routesPath, $content);
        $this->info('The restifyAuth route has been appended to the routes/api.php file.');

        return 0;
    }
}
