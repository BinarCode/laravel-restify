<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

class SecondaryConnectionCompanyRepository extends CompanyRepository
{
    public static $model = SecondaryConnectionCompany::class;

    public static string $uriKey = 'secondary-connection-companies';

    public static bool $globallySearchable = false;
}
