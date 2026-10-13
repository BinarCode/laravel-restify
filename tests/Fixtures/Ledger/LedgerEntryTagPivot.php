<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Ledger;

use Illuminate\Database\Eloquent\Relations\Pivot;
use RuntimeException;

class LedgerEntryTagPivot extends Pivot
{
    protected $connection = LedgerEntry::CONNECTION;

    protected $table = 'ledger_entry_tag';

    protected static function booted(): void
    {
        static::saved(static function (self $pivot): void {
            if ((int) $pivot->ledger_tag_id === LedgerTag::POISONED_ID) {
                throw new RuntimeException('Poisoned ledger tag attached.');
            }
        });

        static::deleted(static function (self $pivot): void {
            if ((int) $pivot->ledger_tag_id === LedgerTag::POISONED_ID) {
                throw new RuntimeException('Poisoned ledger tag detached.');
            }
        });
    }
}
