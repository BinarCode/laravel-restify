<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\MCP\Skill\RestifySkillGenerator;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SkillCommand extends Command
{
    protected $signature = 'restify:skill {--path= : Directory where the skill files are written (default ./restify-skill)}';

    protected $description = 'Generate an Agent Skill (SKILL.md) and OpenAPI description from the MCP-enabled repositories';

    public function handle(RestifySkillGenerator $generator): int
    {
        Restify::ensureRepositoriesLoaded();

        $repositories = $generator->repositories();

        if ($repositories->isEmpty()) {
            $this->warn('No MCP-enabled repositories found. Add the HasMcpTools trait to a repository first.');

            return self::SUCCESS;
        }

        $path = rtrim($this->option('path') ?: getcwd().'/restify-skill', '/');

        File::ensureDirectoryExists($path);

        $this->info("Generating skill for {$repositories->count()} repository(ies)...");

        $repositories->each(function (array $repository): void {
            $this->line("  - {$repository['name']}");
        });

        $skillPath = $path.'/SKILL.md';
        $openApiPath = $path.'/openapi.json';

        File::put($skillPath, $generator->markdown());
        File::put($openApiPath, json_encode($generator->openApi(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->comment("Wrote {$skillPath}");
        $this->comment("Wrote {$openApiPath}");

        return self::SUCCESS;
    }
}
