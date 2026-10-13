<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Ledger;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class LedgerEntryRepository extends Repository
{
    public static string $model = LedgerEntry::class;

    public static string $uriKey = 'ledger-entries';

    public static bool $globallySearchable = false;

    public static function include(): array
    {
        return [
            'tags' => BelongsToMany::make('tags', LedgerTagRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }
}
