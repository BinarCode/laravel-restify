<?php

namespace Binaryk\LaravelRestify\Tests\Factories;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'image' => $this->faker->imageUrl(),
            'title' => $this->faker->title,
            'description' => $this->faker->text,
        ];
    }

    public static function one(array $attributes = []): Post
    {
        return Post::factory()->create($attributes);
    }

    public static function many(int $count = 2, array $attributes = []): Collection
    {
        return app(static::class)->count($count)->create($attributes);
    }
}
