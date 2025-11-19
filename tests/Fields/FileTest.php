<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User\AvatarFile;
use Binaryk\LaravelRestify\Tests\Fixtures\User\AvatarStore;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileTest extends IntegrationTestCase
{
    public function test_can_correctly_fill_the_main_attribute_and_store_file(): void
    {
        Storage::fake();
        Storage::fake('public');

        $model = new User;
        $field = AvatarFile::make('avatar');
        $field->storeAs(function () {
            return 'avatar.jpg';
        });

        $request = RestifyRequest::create('/', 'GET', [], [], [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('avatar.jpg', $model->avatar);

        Storage::disk('public')->assertExists('avatar.jpg');
    }

    public function test_can_upload_file(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('avatar_size'),
                field('avatar_original'),

                Image::make('avatar')
                    ->rules('required')
                    ->disk('customDisk')
                    ->storeOriginalName('avatar_original')
                    ->storeSize('avatar_size')
                    ->storeAs('avatar.jpg'),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar_original' => 'image.jpg',
            'avatar' => '/storage/avatar.jpg',
        ]);

        Storage::disk('customDisk')->assertExists('avatar.jpg');
    }

    public function test_can_prune_prunable_files(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = ($file = UploadedFile::fake()->image('image.jpg'))->storeAs('/', 'avatar.jpg', 'customDisk');
            $user->avatar_size = $file->getSize();
            $user->avatar_original = $file->getClientOriginalName();
            $user->save();
        });

        $this->assertNotNull($user->avatar);
        $this->assertNotNull($user->avatar_size);
        $this->assertNotNull($user->avatar_original);

        Storage::disk('customDisk')->assertExists('avatar.jpg');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')
                    ->disk('customDisk')
                    ->prunable()
                    ->storeAs('avatar.jpg'),
            ]);

        $this->deleteJson(UserRepository::route($user))
            ->assertNoContent();

        Storage::disk('customDisk')->assertMissing('avatar.jpg');
    }

    public function test_cannot_prune_unpruneable_files(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = ($file = UploadedFile::fake()->image('image.jpg'))->storeAs('/', 'avatar.jpg', 'customDisk');
            $user->save();
        });

        Storage::disk('customDisk')->assertExists('avatar.jpg');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->storeAs('avatar.jpg'),
            ]);

        $this->deleteJson(UserRepository::route($user))
            ->assertNoContent();

        Storage::disk('customDisk')->assertExists('avatar.jpg');
    }

    public function test_deletable_file_could_be_deleted(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = ($file = UploadedFile::fake()->image('image.jpg'))->storeAs('/', 'avatar.jpg', 'customDisk');
            $user->save();
        });

        Storage::disk('customDisk')->assertExists('avatar.jpg');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->storeAs('avatar.jpg')->deletable(true),
            ]);

        $this->deleteJson(UserRepository::route($user->getKey().'/field/avatar'))
            ->assertNoContent();

        Storage::disk('customDisk')->assertMissing('avatar.jpg');
    }

    public function test_not_deletable_file_cannot_be_deleted(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = ($file = UploadedFile::fake()->image('image.jpg'))->storeAs('/', 'avatar.jpg', 'customDisk');
            $user->save();
        });

        Storage::disk('customDisk')->assertExists('avatar.jpg');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->storeAs('avatar.jpg')->deletable(false),
            ]);

        $this->deleteJson(UserRepository::route($user.'/field/avatar'))
            ->assertNotFound();
    }

    public function test_can_upload_file_using_storable(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')
                    ->disk('customDisk')
                    ->store(AvatarStore::class),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar' => '/storage/avatar.jpg',
        ]);

        Storage::disk('customDisk')->assertExists('avatar.jpg');
    }

    public function test_model_updating_will_replace_file(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = ($file = UploadedFile::fake()->image('image.jpg'))->storeAs('/', 'avatar.jpg', 'customDisk');
            $user->avatar_size = $file->getSize();
            $user->avatar_original = $file->getClientOriginalName();
            $user->save();
        });

        Storage::disk('customDisk')->assertExists('avatar.jpg');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Image::make('avatar')->disk('customDisk')->storeAs('newAvatar.jpg')->prunable(),
            ]);

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar' => '/storage/newAvatar.jpg',
        ]);

        Storage::disk('customDisk')->assertMissing('avatar.jpg');
        Storage::disk('customDisk')->assertExists('newAvatar.jpg');
    }
}
