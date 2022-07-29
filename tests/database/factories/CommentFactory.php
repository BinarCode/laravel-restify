<?php

namespace Binaryk\LaravelRestify\Tests\Database\Factories;

use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'parent_comment_id' => null,
            'comment' => $this->faker->sentence,
        ];
    }

    public static function one(array $attributes = []): Comment
    {
        return Comment::factory()->create($attributes);
    }

    public static function many(int $count = 2, array $attributes = []): Collection
    {
        return app(static::class)->count($count)->create($attributes);
    }
}
