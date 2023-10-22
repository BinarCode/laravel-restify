<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Tests\Fixtures\User\AvatarStore;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageTest extends IntegrationTestCase
{
    public function test_image_has_default(): void
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

        $this->postJson(UserRepository::route($user), [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'avatar' => UploadedFile::fake()->image('image.jpg'),
                ],
            ],
        ])->assertOk()->assertJsonFragment([
            'avatar' => '/storage/avatar.jpg',
        ]);
    }
}
