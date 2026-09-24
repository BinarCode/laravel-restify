<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

class CompanyBySlugRepository extends CompanyRepository
{
    public static $model = CompanyBySlug::class;

    public static $uriKey = 'companies-by-slug';
}
