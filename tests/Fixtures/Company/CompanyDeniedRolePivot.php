<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyDeniedRolePivot extends Pivot
{
    protected $table = 'company_denied_role';
}
