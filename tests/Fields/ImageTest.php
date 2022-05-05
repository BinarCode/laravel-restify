<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Tests\Fixtures\User\AvatarStore;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageTest extends IntegrationTest
{
    public function test_image_has_default()
    {
        $image = Image::make('image')->default($default = 'https://lorempixel.com/500x500.png');

        $repository = UserRepository::partialMock();

        $this->assertSame(
            $default,
            $image->resolveForShow($repository)->value
        );

        $this->assertSame(
            $default,
            $image->resolveForIndex($repository)->value
        );
    }

    public function test_ignore_image_default_value_when_image_exists(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->store(AvatarStore::class)->default('foo.png'),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::uriKey()."/{$user->getKey()}", [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar' => '/storage/avatar.jpg',
        ]);
    }
}
