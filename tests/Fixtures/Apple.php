<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Apple extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'title',
        'color',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toArray()
    {
        return parent::toArray();
    }
}
