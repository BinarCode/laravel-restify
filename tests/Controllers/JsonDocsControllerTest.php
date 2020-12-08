<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\IntegrationTest;

class JsonDocsControllerTest extends IntegrationTest
{
    public function test_can_get_json_docs()
    {
        $this->getJson('json-docs')
            ->dump();
    }
}
