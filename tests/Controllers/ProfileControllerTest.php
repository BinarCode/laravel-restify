<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

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
        $response = $this->getJson('/restify-api/profile')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $response->assertJsonFragment([
            'email' => $this->authenticatedAs->email,
        ]);
    }

    public function test_profile_returns_authenticated_user_with_related_posts()
    {
        $this->getJson('/restify-api/profile?related=posts')
            ->assertStatus(200)
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
        $this->getJson('/restify-api/profile')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'roles',
                ],
            ]);
    }

    public function test_profile_update()
    {
        $response = $this->putJson('restify-api/profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ])
            ->assertStatus(200);

        $response->assertJsonFragment([
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ]);
    }

    public function test_profile_update_password()
    {
        $this->putJson('restify-api/profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ])
            ->assertStatus(200);

        $this->assertTrue(Hash::check('secret', $this->authenticatedAs->password));
    }

    public function test_profile_update_unique_email()
    {
        factory(User::class)->create([
            'email' => 'existing@gmail.com',
        ]);

        $this->putJson('restify-api/profile', [
            'email' => 'existing@gmail.com',
            'name' => 'Eduard',
        ])
            ->assertStatus(400);
    }

    public function test_profile_upload_avatar()
    {
        $file = UploadedFile::fake()->image($this->getTestJpg())->size(100);

        $this->postJson('restify-api/profile/avatar', [
            'avatar' => $file,
        ])
            ->assertStatus(200);
    }

    public function test_profile_validation_from_repository()
    {
        UserRepository::$canUseForProfileUpdate = true;

        $this->putJson('/restify-api/profile', [
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

        $response = $this->getJson('/restify-api/profile')
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

        $response = $this->getJson('/restify-api/profile?related=posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'attributes',
                'relationships' => [
                    'posts' => [
                        [
                            'attributes',
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

        $this->getJson('/restify-api/profile')
            ->assertStatus(200)
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

        $response = $this->putJson('restify-api/profile', [
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ])
            ->assertStatus(200);

        $response->assertJsonFragment([
            'email' => 'contact@binarschool.com',
            'name' => 'Eduard',
        ]);
    }
}
