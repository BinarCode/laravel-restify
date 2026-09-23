<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class PolicyCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_a_policy_for_the_model_derived_from_its_name(): void
    {
        $this->artisan('restify:policy', ['name' => 'Post'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Policies/PostPolicy.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Policies;', $content);
        $this->assertStringContainsString('use App\Models\User;', $content);
        $this->assertStringContainsString('use App\Models\Post;', $content);
        $this->assertStringContainsString('class PostPolicy', $content);
        $this->assertStringContainsString('use HandlesAuthorization;', $content);
        $this->assertStringContainsString('public function show(User $user = null, Post $model): bool', $content);
        $this->assertStringContainsString('public function store(User $user): bool', $content);
    }

    #[Test]
    public function it_does_not_double_append_the_policy_suffix(): void
    {
        $this->artisan('restify:policy', ['name' => 'PostPolicy'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Policies/PostPolicy.php');
    }

    #[Test]
    public function it_singularizes_the_model_name(): void
    {
        $this->artisan('restify:policy', ['name' => 'Posts'])
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Policies/PostsPolicy.php');
        $this->assertStringContainsString('use App\Models\Post;', $content);
        $this->assertStringContainsString('Post $model', $content);
    }

    #[Test]
    public function it_does_not_duplicate_the_user_import_when_the_policy_is_for_the_user_model(): void
    {
        $this->artisan('restify:policy', ['name' => 'User'])
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Policies/UserPolicy.php');

        $this->assertSame(1, substr_count($content, 'use App\Models\User;'));
        $this->assertStringContainsString('User $model', $content);
    }

    #[Test]
    public function the_model_option_is_documented_but_has_no_effect_on_the_generated_policy(): void
    {
        $this->artisan('restify:policy', ['name' => 'Post', '--model' => 'SomethingElse'])
            ->assertExitCode(0);

        $content = File::get($this->generatedAppPath.'/Policies/PostPolicy.php');
        $this->assertStringContainsString('use App\Models\Post;', $content);
        $this->assertStringNotContainsString('SomethingElse', $content);
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_policy_without_force(): void
    {
        $path = $this->generatedAppPath.'/Policies/PostPolicy.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:policy', ['name' => 'Post'])
            ->expectsOutputToContain('Policy already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_policy_with_force(): void
    {
        $path = $this->generatedAppPath.'/Policies/PostPolicy.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:policy', ['name' => 'Post', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class PostPolicy', File::get($path));
    }
}
