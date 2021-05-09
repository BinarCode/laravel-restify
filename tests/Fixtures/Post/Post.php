<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Models\Concerns\HasActionLogs;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Post
 * @property mixed $id
 * @property mixed $user_id
 * @property mixed $image
 * @property mixed $title
 * @property mixed $description
 * @property mixed $category
 * @property mixed $is_active
 * @package Binaryk\LaravelRestify\Tests\Fixtures\Post
 */
class Post extends Model
{
    use HasActionLogs;
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'image',
        'title',
        'description',
        'category',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
