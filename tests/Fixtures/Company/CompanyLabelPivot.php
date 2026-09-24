<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyLabelPivot extends Pivot
{
    protected $table = 'company_label';
}
