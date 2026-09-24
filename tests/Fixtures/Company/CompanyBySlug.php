<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

class CompanyBySlug extends Company
{
    protected $table = 'companies';

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
