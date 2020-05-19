<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'id',
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id')
            ->withPivot([
                'is_admin'
            ])
            ->withTimestamps();
    }
}
