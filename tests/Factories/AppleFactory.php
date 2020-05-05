<?php

use Binaryk\LaravelRestify\Tests\Fixtures\Apple;
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

$factory->define(Apple::class, function (Faker $faker) {
    return [
        'title' => $faker->text(50),
    ];
});
