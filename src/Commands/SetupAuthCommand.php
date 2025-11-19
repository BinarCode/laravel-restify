<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;

class SetupAuthCommand extends Command
{
    protected $signature = 'restify:setup-auth';

    protected $description = 'Configure Sanctum and add auth routes';

    public function handle()
    {
        $this->info('Configure Sanctum and add auth routes');

        $this->call(PrepareSanctumCommand::class);
        $this->call(RestifyAuthMacroCommand::class);
    }
}
