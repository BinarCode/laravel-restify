<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
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
        $succeeded = true;

        foreach (['route:clear', 'cache:clear', 'config:clear', 'view:clear'] as $command) {
            if ($this->call($command) !== self::SUCCESS) {
                $succeeded = false;
            }
        }

        $this->clearRepositoryCaches();

        return $succeeded ? self::SUCCESS : self::FAILURE;
    }

    /**
     * `cache:clear` never reaches a repository caching to a store of its own.
     */
    private function clearRepositoryCaches(): void
    {
        Restify::ensureRepositoriesLoaded();

        /** @var class-string<Repository> $repository */
        foreach (Restify::$repositories as $repository) {
            $repository::clearCache();
        }
    }
}
