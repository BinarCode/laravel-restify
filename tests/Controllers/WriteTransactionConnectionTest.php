<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithLedgerConnection;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntry;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntryRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerEntryTagPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Ledger\LedgerTag;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class WriteTransactionConnectionTest extends IntegrationTestCase
{
    use InteractsWithLedgerConnection;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.logs.all' => true]);

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
        $entry = $this->insertEntry('original');

        $this->putJson(LedgerEntryRepository::route($entry), [
            'title' => LedgerEntry::POISONED_TITLE,
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey(), 'title' => 'original']);
    }

    #[Test]
    public function patch_rolls_back_the_row_when_a_later_step_fails(): void
    {
        $entry = $this->insertEntry('original');

        $this->patchJson(LedgerEntryRepository::route($entry), [
            'title' => LedgerEntry::POISONED_TITLE,
        ])->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey(), 'title' => 'original']);
    }

    #[Test]
    public function destroy_keeps_the_row_when_a_later_step_fails(): void
    {
        $entry = $this->insertEntry(LedgerEntry::POISONED_TITLE);

        $this->deleteJson(LedgerEntryRepository::route($entry))->assertInternalServerError();

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey()]);
    }

    #[Test]
    public function update_bulk_rolls_back_every_row_when_a_later_row_fails(): void
    {
        $first = $this->insertEntry('first');
        $second = $this->insertEntry('second');

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
        $first = $this->insertEntry('first');
        $second = $this->insertEntry(LedgerEntry::POISONED_TITLE);

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
        $entry = $this->insertEntry('entry');
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
        $entry = $this->insertEntry('entry');
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
    public function a_successful_patch_writes_an_action_log_on_the_default_connection(): void
    {
        $entry = $this->insertEntry('original');

        $this->patchJson(LedgerEntryRepository::route($entry), ['title' => 'patched'])->assertOk();

        $this->assertDatabaseHas(ActionLog::class, ['actionable_id' => $entry->getKey()]);
    }

    #[Test]
    #[TestWith(['store'])]
    #[TestWith(['patch'])]
    #[TestWith(['destroy_bulk'])]
    public function a_failed_write_leaves_no_orphan_action_log(string $endpoint): void
    {
        $entry = $this->insertEntry($endpoint === 'destroy_bulk' ? LedgerEntry::POISONED_TITLE : 'original');

        match ($endpoint) {
            'store' => $this->postJson(LedgerEntryRepository::route(), ['title' => LedgerEntry::POISONED_TITLE]),
            'patch' => $this->patchJson(LedgerEntryRepository::route($entry), ['title' => LedgerEntry::POISONED_TITLE]),
            'destroy_bulk' => $this->deleteJson(LedgerEntryRepository::route('bulk/delete'), [$entry->getKey()]),
        };

        $this->assertDatabaseCount(ActionLog::class, 0);
    }

    #[Test]
    #[TestWith(['show'])]
    #[TestWith(['index'])]
    public function an_action_that_fails_rolls_back_its_writes_on_the_model_connection(string $scope): void
    {
        $entry = $this->insertEntry('original');

        $action = new class extends Action
        {
            public static $uriKey = 'ledger-failing-action';

            public function handle(ActionRequest $request, Collection|Model $models): JsonResponse
            {
                foreach (Collection::wrap($models) as $entry) {
                    $entry->update(['title' => 'changed']);
                }

                throw new RuntimeException('Action failed after writing.');
            }
        };

        LedgerEntryRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        match ($scope) {
            'show' => $this->postJson(LedgerEntryRepository::route($entry, action: $action)),
            'index' => $this->postJson(LedgerEntryRepository::route('actions', query: ['action' => $action->uriKey()]), [
                'repositories' => [$entry->getKey()],
            ]),
        };

        $this->assertDatabaseHas(LedgerEntry::class, ['id' => $entry->getKey(), 'title' => 'original']);
    }

    #[Test]
    #[TestWith(['store'])]
    #[TestWith(['update'])]
    #[TestWith(['patch'])]
    #[TestWith(['destroy'])]
    #[TestWith(['update_bulk'])]
    #[TestWith(['destroy_bulk'])]
    #[TestWith(['attach'])]
    #[TestWith(['detach'])]
    public function a_default_connection_model_still_transacts_on_the_default_connection(string $endpoint): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'original']);
        $company = Company::factory()->create();
        $staff = User::factory()->create();
        $company->staff()->attach($staff);

        if ($endpoint === 'attach') {
            $company->staff()->detach($staff);
        }

        /** @var list<string> $transactionConnections */
        $transactionConnections = [];

        Event::listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$transactionConnections): void {
            $transactionConnections[] = $event->connectionName;
        });

        match ($endpoint) {
            'store' => $this->postJson(PostRepository::route(), ['title' => 'created'])->assertCreated(),
            'update' => $this->putJson(PostRepository::route($post), ['title' => 'updated'])->assertOk(),
            'patch' => $this->patchJson(PostRepository::route($post), ['title' => 'patched'])->assertOk(),
            'destroy' => $this->deleteJson(PostRepository::route($post))->assertNoContent(),
            'update_bulk' => $this->postJson(PostRepository::route('bulk/update'), [['id' => $post->getKey(), 'title' => 'bulk updated']])->assertOk(),
            'destroy_bulk' => $this->deleteJson(PostRepository::route('bulk/delete'), [$post->getKey()])->assertOk(),
            'attach' => $this->postJson(CompanyRepository::route("{$company->getKey()}/attach/staff"), ['staff' => [$staff->getKey()]])->assertCreated(),
            'detach' => $this->postJson(CompanyRepository::route("{$company->getKey()}/detach/staff"), ['staff' => [$staff->getKey()]])->assertNoContent(),
        };

        $this->assertNotEmpty($transactionConnections);
        $this->assertSame([DB::getDefaultConnection()], array_values(array_unique($transactionConnections)));
    }

    private function insertEntry(string $title): LedgerEntry
    {
        $id = DB::connection(LedgerEntry::CONNECTION)->table('ledger_entries')->insertGetId([
            'title' => $title,
        ]);

        return LedgerEntry::query()->findOrFail($id);
    }
}
