<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Base64File;
use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Base64FileTest extends IntegrationTestCase
{
    protected string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected string $jpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

    public function test_can_store_base64_file(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertNotNull($model->avatar);
        $this->assertStringEndsWith('.png', $model->avatar);

        Storage::disk('customDisk')->assertExists($model->avatar);
    }

    public function test_can_store_base64_file_with_custom_path(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')->disk('customDisk')->path('signatures/user-1');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertNotNull($model->avatar);
        $this->assertStringStartsWith('signatures/user-1/', $model->avatar);
        $this->assertStringEndsWith('.png', $model->avatar);

        Storage::disk('customDisk')->assertExists($model->avatar);
    }

    public function test_can_store_base64_file_with_store_as_string(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeAs('custom-name');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('custom-name.png', $model->avatar);

        Storage::disk('customDisk')->assertExists('custom-name.png');
    }

    public function test_can_store_base64_file_with_store_as_callback(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeAs(fn () => 'callback-name');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('callback-name.png', $model->avatar);

        Storage::disk('customDisk')->assertExists('callback-name.png');
    }

    public function test_can_store_base64_file_with_store_as_including_extension(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeAs('avatar.png');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('avatar.png', $model->avatar);

        Storage::disk('customDisk')->assertExists('avatar.png');
    }

    public function test_can_store_original_name_column(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeOriginalName('avatar_original');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('base64-upload.png', $model->avatar_original);
    }

    public function test_can_store_original_name_from_callback(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeAs(fn () => 'signature')
            ->storeOriginalName('avatar_original');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('signature.png', $model->avatar_original);
    }

    public function test_can_store_size_column(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->storeSize('avatar_size');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertNotNull($model->avatar_size);
        $this->assertIsInt($model->avatar_size);
        $this->assertGreaterThan(0, $model->avatar_size);
    }

    public function test_can_upload_base64_via_repository(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('avatar_size'),
                field('avatar_original'),

                Base64File::make('avatar')
                    ->disk('customDisk')
                    ->storeOriginalName('avatar_original')
                    ->storeSize('avatar_size')
                    ->resolveUsingFullUrl()
                    ->storeAs('avatar'),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::route($user), [
            'avatar' => $this->pngBase64,
        ])->assertOk()->assertJsonFragment([
            'avatar_original' => 'base64-upload.png',
            'avatar' => '/storage/avatar.png',
        ]);

        Storage::disk('customDisk')->assertExists('avatar.png');
    }

    public function test_detects_jpeg_extension(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->jpegBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertStringEndsWith('.jpg', $model->avatar);

        Storage::disk('customDisk')->assertExists($model->avatar);
    }

    public function test_can_prune_prunable_base64_files(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = 'avatar.png';
            $user->save();
        });

        Storage::disk('customDisk')->put('avatar.png', 'content');
        Storage::disk('customDisk')->assertExists('avatar.png');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('customDisk')
                    ->prunable()
                    ->storeAs('avatar'),
            ]);

        $this->deleteJson(UserRepository::route($user))
            ->assertNoContent();

        Storage::disk('customDisk')->assertMissing('avatar.png');
    }

    public function test_prunable_replaces_existing_file_on_update(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = 'old-avatar.png';
            $user->save();
        });

        Storage::disk('customDisk')->put('old-avatar.png', 'old content');
        Storage::disk('customDisk')->assertExists('old-avatar.png');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('customDisk')
                    ->storeAs('new-avatar')
                    ->prunable(),
            ]);

        $this->postJson(UserRepository::route($user), [
            'avatar' => $this->pngBase64,
        ])->assertOk();

        Storage::disk('customDisk')->assertMissing('old-avatar.png');
        Storage::disk('customDisk')->assertExists('new-avatar.png');
    }

    public function test_deletable_base64_file_can_be_deleted(): void
    {
        Storage::fake('customDisk');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = 'avatar.png';
            $user->save();
        });

        Storage::disk('customDisk')->put('avatar.png', 'content');
        Storage::disk('customDisk')->assertExists('avatar.png');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('customDisk')
                    ->deletable(true),
            ]);

        $this->deleteJson(UserRepository::route($user->getKey().'/field/avatar'))
            ->assertNoContent();

        Storage::disk('customDisk')->assertMissing('avatar.png');
    }

    public function test_falls_back_to_parent_for_url_input(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('customDisk'),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::route($user), [
            'avatar' => 'https://example.com/image.png',
        ])->assertOk()->assertJsonFragment([
            'avatar' => 'https://example.com/image.png',
        ]);
    }

    public function test_falls_back_to_parent_for_file_upload(): void
    {
        Storage::fake('customDisk');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('customDisk')
                    ->resolveUsingFullUrl()
                    ->storeAs('uploaded-file'),
            ]);

        $user = $this->mockUsers()->first();

        $this->postJson(UserRepository::route($user), [
            'avatar' => UploadedFile::fake()->image('test.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar' => '/storage/uploaded-file.jpg',
        ]);

        Storage::disk('customDisk')->assertExists('uploaded-file.jpg');
    }

    public function test_ignores_invalid_input(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $model->avatar = 'existing.png';

        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => 'not-a-valid-base64-or-url',
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('existing.png', $model->avatar);
    }

    public function test_ignores_empty_input(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $model->avatar = 'existing.png';

        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => '',
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('existing.png', $model->avatar);
    }

    public function test_ignores_null_input(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $model->avatar = 'existing.png';

        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => null,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('existing.png', $model->avatar);
    }

    public function test_can_handle_raw_base64_without_data_uri(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $rawBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $field = Base64File::make('avatar')->disk('customDisk');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $rawBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertNotNull($model->avatar);
        $this->assertStringEndsWith('.bin', $model->avatar);

        Storage::disk('customDisk')->assertExists($model->avatar);
    }

    public function test_can_store_with_path_and_store_as(): void
    {
        Storage::fake('customDisk');

        $model = new User;
        $field = Base64File::make('avatar')
            ->disk('customDisk')
            ->path('users/avatars')
            ->storeAs('my-avatar');

        $request = RestifyRequest::create('/', 'POST', [
            'avatar' => $this->pngBase64,
        ]);

        $field->fillAttribute($request, $model);

        $this->assertEquals('users/avatars/my-avatar.png', $model->avatar);

        Storage::disk('customDisk')->assertExists('users/avatars/my-avatar.png');
    }

    public function test_resolves_temporary_url(): void
    {
        Storage::fake('s3');

        $user = tap($this->mockUsers()->first(), function (User $user) {
            $user->avatar = 'avatar.png';
            $user->save();
        });

        Storage::disk('s3')->put('avatar.png', 'content');

        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                Base64File::make('avatar')
                    ->disk('s3')
                    ->resolveUsingFullUrl(),
            ]);

        $response = $this->getJson(UserRepository::route($user))->assertOk();

        $this->assertStringContainsString('/storage/avatar.png', $response->json('data.attributes.avatar'));
    }
}
