<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntry;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntryRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerTag;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerTagRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

trait InteractsWithLedgerConnection
{
    protected function setUpInteractsWithLedgerConnection(): void
    {
        config(['database.connections.'.LedgerEntry::CONNECTION => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]]);

        $schema = Schema::connection(LedgerEntry::CONNECTION);

        $schema->create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        $schema->create('ledger_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('ledger_entry_tag', function (Blueprint $table): void {
            $table->unsignedBigInteger('ledger_entry_id');
            $table->unsignedBigInteger('ledger_tag_id');
        });

        Gate::policy(LedgerEntry::class, LedgerPolicy::class);
        Gate::policy(LedgerTag::class, LedgerPolicy::class);

        Restify::repositories([
            LedgerEntryRepository::class,
            LedgerTagRepository::class,
        ]);
    }
}
