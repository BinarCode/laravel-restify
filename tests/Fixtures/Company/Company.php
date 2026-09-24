<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Company;

use Binaryk\LaravelRestify\Tests\Fixtures\Label\EnumKeyedLabel;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\StringableKeyedLabel;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\User\IntegerKeyedUser;
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_role', 'company_id', 'role_name', 'id', 'name')
            ->using(CompanyRolePivot::class)
            ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id')
            ->using(CompanyUserPivot::class)
            ->withTimestamps();
    }

    public function deniedRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_denied_role', 'company_id', 'role_id')
            ->using(CompanyDeniedRolePivot::class)
            ->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'company_label', 'company_id', 'label_code')
            ->using(CompanyLabelPivot::class)
            ->withTimestamps();
    }

    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(EnumKeyedLabel::class, 'company_label', 'company_id', 'label_code')
            ->using(CompanyLabelPivot::class)
            ->withTimestamps();
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(StringableKeyedLabel::class, 'company_label', 'company_id', 'label_code')
            ->using(CompanyLabelPivot::class)
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(IntegerKeyedUser::class, 'company_user', 'company_id', 'user_id')
            ->using(CompanyUserPivot::class)
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
