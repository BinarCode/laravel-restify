<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Illuminate\Database\Eloquent\Model;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class Post extends Model implements RestifySearchable
{
    use InteractWithSearch;

    protected $fillable = [
        'id',
        'user_id',
        'image',
        'title',
        'description',
    ];
}
