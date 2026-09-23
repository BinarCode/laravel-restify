<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'owner_id',
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

    /**
     * A relation whose related key is the related model's `name` column, not its
     * primary key - fixture for the non-primary-key related key attach/detach/sync tests.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_role', 'company_id', 'role_name', 'id', 'name')
            ->using(CompanyRolePivot::class)
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
