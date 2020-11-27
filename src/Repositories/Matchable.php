<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface Matchable
{
    public function handle(Request $request, Builder $query);
}
