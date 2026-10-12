<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithLedgerConnection;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntry;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntryRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntryTagPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerTag;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class WriteTransactionConnectionTest extends IntegrationTestCase
{
    use InteractsWithLedgerConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function store_rolls_back_the_row_when_a_later_step_fails(): void
    {
        $this->postJson(LedgerEntryRepository::route(), [
            'title' => LedgerEntry::POISONED_TITLE,
        ])->assertInternalServerError();

        $this->assertDatabaseCount(LedgerEntry::class, 0);
    }

    #[Test]
    public function store_bulk_rolls_back_every_row_when_a_later_row_fails(): void
    {
        $this->postJson(LedgerEntryRepository::route('bulk'), [
            ['title' => 'first'],
            ['title' => LedgerEntry::POISONED_TITLE],
        ])->assertInternalServerError();

        $this->assertDatabaseCount(LedgerEntry::class, 0);
    }

    #[Test]
    public function update_rolls_back_the_row_when_a_later_step_fails(): void
    {
        $entry = LedgerEntry::query()->create(['title' => 'original']);

        $this->putJson(LedgerEntryRepository::route($entry), [
            'title' => LedgerEntry::POISONED_TITLE,
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey(), 'title' => 'original']);
    }

    #[Test]
    public function patch_rolls_back_the_row_when_a_later_step_fails(): void
    {
        $entry = LedgerEntry::query()->create(['title' => 'original']);

        $this->patchJson(LedgerEntryRepository::route($entry), [
            'title' => LedgerEntry::POISONED_TITLE,
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey(), 'title' => 'original']);
    }

    #[Test]
    public function destroy_keeps_the_row_when_a_later_step_fails(): void
    {
        $entry = $this->createPoisonedEntry();

        $this->deleteJson(LedgerEntryRepository::route($entry))->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey()]);
    }

    #[Test]
    public function update_bulk_rolls_back_every_row_when_a_later_row_fails(): void
    {
        $first = LedgerEntry::query()->create(['title' => 'first']);
        $second = LedgerEntry::query()->create(['title' => 'second']);

        $this->postJson(LedgerEntryRepository::route('bulk/update'), [
            ['id' => $first->getKey(), 'title' => 'first updated'],
            ['id' => $second->getKey(), 'title' => LedgerEntry::POISONED_TITLE],
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $first->getKey(), 'title' => 'first']);
        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $second->getKey(), 'title' => 'second']);
    }

    #[Test]
    public function destroy_bulk_keeps_every_row_when_a_later_row_fails(): void
    {
        $first = LedgerEntry::query()->create(['title' => 'first']);
        $second = $this->createPoisonedEntry();

        $this->deleteJson(LedgerEntryRepository::route('bulk/delete'), [
            $first->getKey(),
            $second->getKey(),
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $first->getKey()]);
        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $second->getKey()]);
    }

    #[Test]
    public function attach_rolls_back_every_pivot_when_a_later_pivot_fails(): void
    {
        $entry = LedgerEntry::query()->create(['title' => 'entry']);
        $tag = LedgerTag::query()->create(['name' => 'regular']);
        $poisonedTag = LedgerTag::query()->create(['id' => LedgerTag::POISONED_ID, 'name' => 'poisoned']);

        $this->postJson(LedgerEntryRepository::route("{$entry->getKey()}/attach/tags"), [
            'tags' => [$tag->getKey(), $poisonedTag->getKey()],
        ])->assertInternalServerError();

        $this->assertDatabaseCount(LedgerEntryTagPivot::class, 0);
    }

    #[Test]
    public function detach_keeps_every_pivot_when_a_later_pivot_fails(): void
    {
        $entry = LedgerEntry::query()->create(['title' => 'entry']);
        $tag = LedgerTag::query()->create(['name' => 'regular']);
        $poisonedTag = LedgerTag::query()->create(['id' => LedgerTag::POISONED_ID, 'name' => 'poisoned']);

        DB::connection(LedgerEntry::CONNECTION)->table('ledger_entry_tag')->insert([
            ['ledger_entry_id' => $entry->getKey(), 'ledger_tag_id' => $tag->getKey()],
            ['ledger_entry_id' => $entry->getKey(), 'ledger_tag_id' => $poisonedTag->getKey()],
        ]);

        $this->postJson(LedgerEntryRepository::route("{$entry->getKey()}/detach/tags"), [
            'tags' => [$tag->getKey(), $poisonedTag->getKey()],
        ])->assertInternalServerError();

        $this->assertDatabaseCount(LedgerEntryTagPivot::class, 2);
    }

    #[Test]
    #[TestWith(['store'])]
    #[TestWith(['update'])]
    #[TestWith(['destroy'])]
    public function a_default_connection_model_still_transacts_on_the_default_connection(string $endpoint): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'original']);

        /** @var list<string> $transactionConnections */
        $transactionConnections = [];

        Event::listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$transactionConnections): void {
            $transactionConnections[] = $event->connectionName;
        });

        match ($endpoint) {
            'store' => $this->postJson(PostRepository::route(), ['title' => 'created'])->assertCreated(),
            'update' => $this->putJson(PostRepository::route($post), ['title' => 'updated'])->assertOk(),
            'destroy' => $this->deleteJson(PostRepository::route($post))->assertNoContent(),
        };

        $this->assertSame([DB::getDefaultConnection()], $transactionConnections);
    }

    private function createPoisonedEntry(): LedgerEntry
    {
        $id = DB::connection(LedgerEntry::CONNECTION)->table('ledger_entries')->insertGetId([
            'title' => LedgerEntry::POISONED_TITLE,
        ]);

        return LedgerEntry::query()->findOrFail($id);
    }
}
