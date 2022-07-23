<?php

use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use function Pest\Laravel\get;

beforeEach(function () {
    UserRepository::$public = true;

    config()->set('restify.middleware', [
        'auth:sanctum' => function ($request, $next) {
            abort(403);
        },
    ]);
});

it('cannot access repositories publicly', function () {
    $this->logout();

    get(UserRepository::route())->assertForbidden();
});
