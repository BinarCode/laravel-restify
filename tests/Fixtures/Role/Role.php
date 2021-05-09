<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Role;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    public function users(): MorphToMany
    {
        return $this->morphToMany(User::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }
}
