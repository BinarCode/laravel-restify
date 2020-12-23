<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUserPivot extends Pivot
{
    protected $casts = [
        'is_admin' => 'bool',
    ];
}
