<?php

namespace Binaryk\LaravelRestify\Contracts;

use Illuminate\Database\Query\Builder;

interface Passportable
{
    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = []);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return Builder
     */
    public function tokens();
}
