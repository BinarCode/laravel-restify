<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Role;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    public $timestamps = false;

    protected $table = 'model_has_roles';

    protected $guarded = ['id'];
}
