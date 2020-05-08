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

$factory->define(Binaryk\LaravelRestify\Tests\Fixtures\Post::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'image' => $faker->imageUrl(),
        'title' => $faker->title,
        'description' => $faker->text,
    ];
});
