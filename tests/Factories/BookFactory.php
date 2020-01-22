<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Binaryk\LaravelRestify\Tests\Fixtures\Book::class, function (Faker $faker) {
    return [
        'title' => $faker->text(30),
        'description' => $faker->text,
        'author' => $faker->name,
        'price' => $faker->randomFloat(),
        'stock' => $faker->numberBetween(0, 100),
    ];
});
