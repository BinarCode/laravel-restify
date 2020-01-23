<?php

namespace Binaryk\LaravelRestify\Contracts;

use Illuminate\Database\Query\Builder;

interface Airlockable
{
    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @return \Laravel\Airlock\NewAccessToken
     */
    public function createToken(string $name, array $abilities = ['*']);

    /**
     * @return Builder
     */
    public function tokens();
}
