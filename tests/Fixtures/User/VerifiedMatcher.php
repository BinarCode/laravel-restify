<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class VerifiedMatcher extends MatchFilter
{
    public function filter(RestifyRequest $request, $query, $value, bool $no_eager = false)
    {
        if ($request->boolean($this->getQueryKey())) {
            $query->whereNotNull('email_verified_at');
        } else {
            $query->whereNull('email_verified_at');
        }
    }
}
