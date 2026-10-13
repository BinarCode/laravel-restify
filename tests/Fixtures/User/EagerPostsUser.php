<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EagerPostsUser extends User
{
    protected $table = 'users';

    protected $with = ['posts'];

    protected $withCount = ['posts'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}
