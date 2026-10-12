<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Ledger;

use Illuminate\Database\Eloquent\Model;

class LedgerTag extends Model
{
    public const POISONED_ID = 99;

    protected $connection = LedgerEntry::CONNECTION;

    protected $table = 'ledger_tags';

    protected $fillable = [
        'id',
        'name',
    ];
}
