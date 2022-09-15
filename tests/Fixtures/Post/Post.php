<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Models\Concerns\HasActionLogs;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Post.
 *
 * @property mixed $id
 * @property mixed $user_id
 * @property mixed $edited_by
 * @property mixed $image
 * @property string $title
 * @property string $description
 * @property mixed $category
 * @property bool $is_active
 */
class Post extends Model
{
    use HasActionLogs;
    use HasFactory;

    protected $fillable = [
        'id',
        'edited_by',
        'user_id',
        'image',
        'title',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
