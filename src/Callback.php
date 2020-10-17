<?php

namespace Binaryk\LaravelRestify;


use Illuminate\Http\Request;

interface Callback
{
    public function handle(Request $request);
}
