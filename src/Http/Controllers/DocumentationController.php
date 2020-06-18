<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

class DocumentationController
{
    public function index()
    {
        return view('restify::docs.index');

        return 'ok';
    }
}
