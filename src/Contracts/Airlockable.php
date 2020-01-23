<?php

namespace Binaryk\LaravelRestify\Contracts;

use Illuminate\Database\Query\Builder;

interface Airlockable
{
    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     */
    public function createToken($name, array $scopes = []);

    /**
     * @return Builder
     */
    public function tokens();
}
