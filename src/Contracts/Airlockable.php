<?php

namespace Binaryk\LaravelRestify\Contracts;

interface Airlockable
{
    public function createToken(string $name, array $abilities = ['*']);

    public function tokens();
}
