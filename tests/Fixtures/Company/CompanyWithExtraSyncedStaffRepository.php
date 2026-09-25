<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;

class CompanyWithExtraSyncedStaffRepository extends CompanyRepository
{
    public static string $uriKey = 'companies-with-extra-synced-staff';

    public static bool $globallySearchable = false;

    public static int|string|null $extraStaffId = null;

    public function sync(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        return parent::sync($request, $repositoryId, $pivots->push(static::$extraStaffId));
    }
}
