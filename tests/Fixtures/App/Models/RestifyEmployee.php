<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestifyEmployee extends Model
{
    protected $table = 'restify_employees';

    public $timestamps = true;
}
