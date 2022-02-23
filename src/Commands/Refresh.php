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
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('route:cache');
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->call('view:clear');
        $this->call('route:clear');

        return 0;
    }
}
