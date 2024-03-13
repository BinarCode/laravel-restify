<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Repositories\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AvatarStore implements Storable
{
    public function handle(Request $request, Model $model, $attribute): array
    {
        return [
            'avatar' => $request->file('data.attributes.avatar')->storeAs('/', 'avatar.jpg', 'customDisk'),
        ];
    }
}
