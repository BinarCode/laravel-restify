<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Collection;

class RepositoryAfterBulkTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            WithAfterBulkOverrides::class,
        ]);
    }

    public function test_it_calls_the_overriden_stored_bulk_method(): void
    {
        $user = User::factory()->make();

        $this->postJson(WithAfterBulkOverrides::uriKey().'/bulk', [
            [
                'name' => $user->name,
                'email' => 'test@example.com',
                'password' => $user->password,
            ],
        ])->assertSuccessful();

        $this->assertEquals('stored@test.example.com', $user->first()->email);
    }

    public function test_it_calls_the_overriden_updated_bulk_method(): void
    {
        $user = User::factory()->create();

        $this->postJson(WithAfterBulkOverrides::uriKey().'/bulk/update', [
            [
                'id' => $user->id,
                'email' => 'test@example.com',
            ],
        ])->assertSuccessful();

        $this->assertEquals('updated@test.example.com', $user->fresh()->email);
    }

    public function test_it_calls_the_overriden_saved_bulk_method_for_create(): void
    {
        $user = User::factory()->make();

        $this->postJson(WithAfterBulkOverrides::uriKey().'/bulk', [
            [
                'name' => $user->name,
                'email' => 'test@example.com',
                'password' => $user->password,
            ],
        ])->assertSuccessful();

        $this->assertEquals('John Saved', $user->first()->name);
    }

    public function test_it_calls_the_overriden_saved_bulk_method_for_update(): void
    {
        $user = User::factory()->create();

        $this->postJson(WithAfterBulkOverrides::uriKey().'/bulk/update', [
            [
                'id' => $user->id,
                'email' => 'test@example.com',
            ],
        ])->assertSuccessful();

        $this->assertEquals('John Saved', $user->fresh()->name);
    }

    public function test_it_calls_the_overriden_deleted_bulk_method(): void
    {
        $user = User::factory()->create();

        $this->deleteJson(WithAfterBulkOverrides::uriKey().'/bulk/delete', [
            $user->id,
        ])->assertSuccessful();

        $this->assertDatabaseMissing(User::class, ['id' => $user->id]);

        $this->assertDatabaseHas(User::class, [
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }
}

class WithAfterBulkOverrides extends UserRepository
{
    public static function storedBulk(Collection $repositories, $request)
    {
        $user = User::find($repositories->first()['id']);

        $user->update([
            'email' => 'stored@test.example.com',
        ]);
    }

    public static function updatedBulk(Collection $repositories, $request)
    {
        $user = User::find($repositories->first()['id']);

        $user->update([
            'email' => 'updated@test.example.com',
        ]);
    }

    public static function savedBulk(Collection $repositories, $request)
    {
        $user = User::find($repositories->first()['id']);

        $user->update([
            'name' => 'John Saved',
        ]);
    }

    public static function deletedBulk(Collection $repositories, $request)
    {
        $first = $repositories->first();

        User::factory()->create([
            'email' => $first['email'],
            'name' => $first['name'],
        ]);
    }
}
