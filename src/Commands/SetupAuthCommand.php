<?php

namespace Binaryk\LaravelRestify\Commands;

use Illuminate\Console\Command;

class SetupAuthCommand extends Command
{
    protected $signature = 'restify:setup-auth';

    protected $description = 'Configure Sanctum and add auth routes';

    public function handle(): int
    {
        $this->info('Configure Sanctum and add auth routes');

        $this->call(PrepareSanctumCommand::class);

        return $this->call(RestifyAuthMacroCommand::class);
    }
}
