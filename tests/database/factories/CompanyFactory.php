<?php

namespace Binaryk\LaravelRestify\Tests\Database\Factories;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
