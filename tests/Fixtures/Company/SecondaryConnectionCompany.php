<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

class SecondaryConnectionCompany extends Company
{
    public const CONNECTION = 'restify_secondary';

    protected $connection = self::CONNECTION;

    protected $table = 'companies';
}
