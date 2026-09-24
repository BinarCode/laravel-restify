<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * A repository whose uriKey ('staff') differs from its model's table ('users'),
 * used to exercise related-repository resolution that must key off the uriKey
 * and not the table name.
 */
class StaffRepository extends Repository
{
    public static string $model = User::class;

    public static string $uriKey = 'staff';

    /**
     * Excluded so a second repository over the same model as UserRepository
     * does not double the matches in global-search tests.
     */
    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
        ];
    }
}
