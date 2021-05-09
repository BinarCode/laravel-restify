<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfileControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate(User::factory()->create([
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
        ]));

        $this->mockPosts(
            $this->authenticatedAs->id
        );
    }

    public function test_profile_returns_authenticated_user()
    {
        $response = $this->getJson('profile')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $response->assertJsonFragment([
            'email' => $this->authenticatedAs->email,
        ]);
    }

    public function test_profile_returns_authenticated_user_with_related_posts()
    {
        $this->getJson('profile?related=posts')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        [
                            'title',
                        ],
                    ],
                ],
            ]);
    }

    public function test_profile_returns_authenticated_user_with_meta_profile_data()
    {
        $this->getJson('profile')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'roles',
                ],
            ]);
    }

    public function test_profile_update()
    {
        $response = $this->putJson('profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ])->assertOk();

        $response->assertJsonFragment([
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ]);
    }

    public function test_profile_update_password()
    {
        $this->putJson('profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ])->assertOk();

        $this->assertTrue(Hash::check('secret', $this->authenticatedAs->password));
    }

    public function test_profile_update_unique_email(): void
    {
        User::factory()->create([
            'email' => 'existing@gmail.com',
        ]);

        $this->putJson('profile', [
            'email' => 'existing@gmail.com',
            'name' => 'Eduard',
        ])->assertStatus(422);
    }

    public function test_profile_upload_avatar(): void
    {
        $file = UploadedFile::fake()->image($this->getTestJpg())->size(100);

        $this->postJson('profile/avatar', [
            'avatar' => $file,
        ])->assertOk();
    }

    public function test_profile_validation_from_repository(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->putJson('profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Ed',
        ])
            ->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->has('message')
                ->has('errors')
            );
    }

    public function test_get_profile_can_use_repository(): void
    {
        UserRepository::$canUseForProfile = true;

        $this->getJson('profile')
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->has('data')
                ->where('data.attributes.email', $this->authenticatedAs->email)
                ->etc()
            );
    }

    public function test_profile_returns_authenticated_user_with_related_posts_via_repository(): void
    {
        UserRepository::$canUseForProfile = true;

        $this->getJson('profile?related=posts')
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->has('data')
                ->has('data.attributes')
                ->has('data.relationships.posts')
                ->where('data.attributes.email', $this->authenticatedAs->email)
                ->etc()
            );
    }

    public function test_profile_returns_authenticated_user_with_meta_profile_data_via_repository(): void
    {
        UserRepository::$canUseForProfile = true;

        UserRepository::$metaProfile = [
            'roles' => '',
        ];

        $this->getJson('profile')
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->has('data.attributes')
                ->has('data.meta.roles')
                ->etc()
            );
    }

    public function test_profile_update_via_repository(): void
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->putJson('profile', [
            'email' => $email = 'contact@binarschool.com',
        ])
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->where('data.attributes.email', $email)
            );
    }

    public function test_can_upload_avatar(): void
    {
        Storage::fake('customDisk');

        $mock = UserRepository::partialMock()
            ->shouldReceive('canUseForProfileUpdate')
            ->andReturnTrue()
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

        $this->post('profile', [
            'avatar' => UploadedFile::fake()->image('image.jpg'),
        ])->assertOk()->assertJsonFragment([
            'avatar_original' => 'image.jpg',
            'avatar' => '/storage/avatar.jpg',
        ]);

        Storage::disk('customDisk')->assertExists('avatar.jpg');
    }
}
