<?php

namespace Binaryk\LaravelRestify\Tests\Factories;

use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
