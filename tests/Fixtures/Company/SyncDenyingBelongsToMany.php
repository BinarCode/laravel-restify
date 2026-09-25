<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Auth\Access\AuthorizationException;

class SyncDenyingBelongsToMany extends BelongsToMany
{
    public function authorizeToSync(RestifyRequest $request)
    {
        throw new AuthorizationException;
    }
}
