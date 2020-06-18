<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Route;

class DocumentationControllerTest extends IntegrationTest
{
    public function test_list_routes_visible()
    {
        Route::restifyDocs('restify');

        $this->get('restify/api-docs')
            ->assertOk()
            ->assertViewIs('restify::docs.index');
    }
}
