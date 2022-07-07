<?php

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Support\Collection;

trait Mockers
{
    public function mockUsers($count = 1, array $predefinedEmails = []): Collection
    {
        return Collection::times($count, fn ($i) => User::factory()->create())
            ->merge(collect($predefinedEmails)->each(fn (string $email) => User::factory()->create([
                'email' => $email,
            ])))
            ->shuffle();
    }

    public function mockPosts($userId = null, $count = 1): Collection
    {
        return Collection::times($count, fn () => Post::factory()->create([
            'user_id' => $userId,
        ]))->shuffle();
    }

    protected function mockPost(array $attributes = []): Post
    {
        return Post::factory()->create($attributes);
    }
}
