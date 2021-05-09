<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id')
            ->using(CompanyUserPivot::class)
            ->withPivot([
                'is_admin',
            ])
            ->withTimestamps();
    }
}
