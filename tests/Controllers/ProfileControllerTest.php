<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Fields\Image;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate(factory(User::class)->create([
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

    public function test_profile_update_unique_email()
    {
        factory(User::class)->create([
            'email' => 'existing@gmail.com',
        ]);

        $this->putJson('profile', [
            'email' => 'existing@gmail.com',
            'name' => 'Eduard',
        ])->assertStatus(400);
    }

    public function test_profile_upload_avatar()
    {
        $file = UploadedFile::fake()->image($this->getTestJpg())->size(100);

        $this->postJson('profile/avatar', [
            'avatar' => $file,
        ])->assertOk();
    }

    public function test_profile_validation_from_repository()
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->putJson('profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Ed',
        ])
            ->assertStatus(400)
            ->assertJsonStructure([
                'errors' => [
                    [
                        'name',
                    ],
                ],
            ]);
    }

    public function test_get_profile_can_use_repository()
    {
        UserRepository::$canUseForProfile = true;

        $response = $this->getJson('profile')
            ->assertStatus(200)
            ->assertJsonStructure([
                'attributes',
                'meta',
            ]);

        $response->assertJsonFragment([
            'email' => $this->authenticatedAs->email,
        ]);
    }

    public function test_profile_returns_authenticated_user_with_related_posts_via_repository()
    {
        UserRepository::$canUseForProfile = true;

        $response = $this->getJson('profile?related=posts')
            ->assertOk()
            ->assertJsonStructure([
                'attributes',
                'relationships' => [
                    'posts' => [
                        [
                            'id',
                        ],
                    ],
                ],
            ]);

        $response->assertJsonFragment([
            'email' => $this->authenticatedAs->email,
        ]);
    }

    public function test_profile_returns_authenticated_user_with_meta_profile_data_via_repository()
    {
        UserRepository::$canUseForProfile = true;

        UserRepository::$metaProfile = [
            'roles' => '',
        ];

        $this->getJson('profile')
            ->assertOk()
            ->assertJsonStructure([
                'attributes',
                'meta' => [
                    'roles',
                ],
            ]);
    }

    public function test_profile_update_via_repository()
    {
        UserRepository::$canUseForProfileUpdate = true;

        $response = $this->putJson('profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ])->assertOk();

        $response->assertJsonFragment([
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ]);
    }

    public function test_can_upload_avatar()
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
                    ->storeAs('avatar.jpg')
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
