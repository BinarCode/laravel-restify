<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

        $this->flushRepositoryCacheStore();

        return $succeeded ? self::SUCCESS : self::FAILURE;
    }

    /**
     * `cache:clear` only flushes the default cache store, so a repository
     * caching to a different store (`restify.repositories.cache.store`)
     * would otherwise survive a refresh.
     */
    private function flushRepositoryCacheStore(): void
    {
        if (! config('restify.repositories.cache.enabled', false)) {
            return;
        }

        $store = config('restify.repositories.cache.store');

        if (! is_string($store) || $store === config('cache.default')) {
            return;
        }

        Cache::store($store)->flush();
    }
}
