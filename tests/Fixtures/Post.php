<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class Post extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
    ];
}
