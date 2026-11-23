<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Binaryk\LaravelRestify\Tests\Fixtures\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * An application with two kinds of accounts: the users of the `users` table on the default guard, and the admins of
 * the `admins` table on a guard of their own.
 */
trait InteractsWithAdminGuard
{
    protected function setUpInteractsWithAdminGuard(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        config([
            'auth.providers.admins' => ['driver' => 'eloquent', 'model' => Admin::class],
            'auth.guards.'.Admin::GUARD => ['driver' => 'session', 'provider' => 'admins'],
        ]);
    }

    /**
     * An admin stored under the given key, read back from its table as a guard would read it.
     *
     * @template TAdmin of Model
     *
     * @param  array<string, mixed>  $attributes
     * @param  class-string<TAdmin>  $model
     * @return TAdmin
     */
    protected function createAdmin(int|string $id, array $attributes = [], string $model = Admin::class): Model
    {
        DB::table('admins')->insert($attributes + [
            'id' => $id,
            'name' => 'Admin Name',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin-password'),
        ]);

        return $model::query()->findOrFail($id);
    }

    protected function adminColumn(string $column, int|string $id): mixed
    {
        return DB::table('admins')->where('id', $id)->value($column);
    }
}
