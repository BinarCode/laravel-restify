<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class ParentCallingSyncBelongsToMany extends BelongsToMany
{
    public static int $authorizeToSyncCalls = 0;

    public function authorizeToSync(RestifyRequest $request)
    {
        static::$authorizeToSyncCalls++;

        return parent::authorizeToSync($request);
    }
}
