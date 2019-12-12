<?php

namespace Binaryk\LaravelRestify\Contracts;

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
}
