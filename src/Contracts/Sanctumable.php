<?php

namespace Binaryk\LaravelRestify\Contracts;

interface Sanctumable
{
    public function createToken(string $name, array $abilities = ['*']);
}
