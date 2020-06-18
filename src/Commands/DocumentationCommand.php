<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;

class DocumentationCommand extends Command
{
    protected $name = 'restify:doc';

    protected $description = 'Create API documentation.';

    public function handle()
    {
        $this->info("-----------Generating documentation----------------------");

        $targetFile = '/resources/index.md';

        $markdown = 'lorem ipsum';

        file_put_contents($targetFile, $markdown);

        $this->info("-----------Documentation generated----------------------");
    }
}
