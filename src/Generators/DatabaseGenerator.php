<?php

namespace Binaryk\LaravelRestify\Generators;

use Binaryk\LaravelRestify\Traits\Make;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\Column;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseGenerator
{
    use Make;

    private Faker $faker;

    public function __construct()
    {
        $this->faker = app(Faker::class);
    }

    public function fake(Column $columnDefinition)
    {
        $column = $columnDefinition->getName();
        $type = $columnDefinition->getType()->getName();

        switch ($type) {
            case 'text':
                return $this->faker->randomHtml(2);
            case 'string':
                return $this->string($column);
            case 'datetime':
                return Carbon::now();
            case 'boolean':
                return $this->faker->boolean;
            case 'bigint':
            case 'int':
            case 'integer':
                return $this->integer($columnDefinition, $column);
        }
    }

    public function string(string $column): string
    {
        if (Str::contains($column, 'email')) {
            return $this->faker->email;
        }

        if (Str::contains($column, 'password')) {
            return Hash::make('secret');
        }

        if (Str::contains($column, 'uuid')) {
            return Str::orderedUuid();
        }

        if (Str::contains($column, 'image') || Str::contains($column, 'picture') || Str::contains($column, 'avatar')) {
            return $this->faker->imageUrl();
        }

        return $this->faker->text(50);
    }

    public function integer(Column $columnDefinition, $column): ?int
    {
        if ($columnDefinition->getAutoincrement() === true) {
            //primary key
            return null;
        }

        if (Str::endsWith($column, '_id')) {
            $guessTable = Str::pluralStudly(Str::beforeLast($column, '_id'));

            if (Schema::hasTable($guessTable)) {
                return optional(DB::table($guessTable)->inRandomOrder()->first())->id ?? $this->faker->randomNumber(4);
            }
        }

        return $this->faker->randomNumber(4);
    }
}
