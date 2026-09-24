<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

use Illuminate\Database\Eloquent\Model;

/**
 * A string-keyed model whose key column is case-insensitive (NOCASE on sqlite),
 * like MySQL's default utf8mb4 collations.
 */
class Label extends Model
{
    protected $table = 'labels';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];
}
