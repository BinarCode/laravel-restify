<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Role;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    public function users()
    {
        return $this->morphToMany(User::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }
}
