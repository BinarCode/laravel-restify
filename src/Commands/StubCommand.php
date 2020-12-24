<?php

namespace Binaryk\LaravelRestify\Commands;

use Binaryk\LaravelRestify\Generators\DatabaseGenerator;
use Doctrine\DBAL\Schema\Column;
use Faker\Generator as Faker;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StubCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'restify:stub {table} {--count= : The number of entries}';

    protected $description = 'Based on table definition, will try to seed the table with mock data.';

    /**
     * @var Faker
     */
    private $faker;

    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(Resolver $resolver, Faker $faker)
    {
        parent::__construct();
        $this->resolver = $resolver;
        $this->faker = $faker;
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return true;
        }

        DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        collect(explode(',', $this->argument('table')))->each(function ($table) {
            if (! $this->resolver->connection()->getSchemaBuilder()->hasTable($table)) {
                return false;
            }

            Collection::times($count = $this->option('count') ?? 1)->each(fn () => $this->make($table));

            $this->info("Seeded {$count} {$table}.");
        });
    }

    protected function make($table)
    {
        $data = [];

        collect(Schema::getColumnListing($table))->each(function ($column) use (&$data, $table) {
            $connection = Schema::getConnection();
            /** * @var Column $columnDefinition */
            $columnDefinition = $connection->getDoctrineColumn($table, $column);

            if ($value = DatabaseGenerator::make()->fake($columnDefinition)) {
                $data[$column] = $value;
            }
        });

        $id = DB::table($table)->insertGetId($data);

        $this->info('Created '.Str::singular(Str::studly($table)).' with id:'.$id);
    }
}
