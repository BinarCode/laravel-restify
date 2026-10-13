<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Ledger;

use Binaryk\LaravelRestify\Models\Concerns\HasActionLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

class LedgerEntry extends Model
{
    use HasActionLogs;

    public const CONNECTION = 'restify_ledger';

    public const POISONED_TITLE = 'poisoned';

    protected $connection = self::CONNECTION;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'title',
    ];

    protected static function booted(): void
    {
        static::saved(static function (self $entry): void {
            if ($entry->title === self::POISONED_TITLE) {
                throw new RuntimeException('Poisoned ledger entry saved.');
            }
        });

        static::deleted(static function (self $entry): void {
            if ($entry->title === self::POISONED_TITLE) {
                throw new RuntimeException('Poisoned ledger entry deleted.');
            }
        });
    }

    /**
     * @return BelongsToMany<LedgerTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(LedgerTag::class, 'ledger_entry_tag', 'ledger_entry_id', 'ledger_tag_id')
            ->using(LedgerEntryTagPivot::class);
    }
}
