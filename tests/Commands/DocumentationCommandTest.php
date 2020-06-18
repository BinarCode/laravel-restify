<?php

namespace Binaryk\LaravelRestify\Tests\Commands;

use Binaryk\LaravelRestify\Tests\IntegrationTest;

class DocumentationCommandTest extends IntegrationTest
{

    public function test_documentation_command_called()
    {
        $this->artisan('restify:doc')->assertExitCode(0);
    }

}
