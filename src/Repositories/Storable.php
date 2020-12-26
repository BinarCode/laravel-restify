<?php

namespace Binaryk\LaravelRestify\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface Storable
{
    public function handle(Request $request, Model $model, $attribute): array;
}
