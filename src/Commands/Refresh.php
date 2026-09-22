<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;

class Refresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restify:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all laravel caches (routes, cache, config and view)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('route:clear');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');

        return 0;
    }
}
