<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatedEagerLoadingTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function tearDown(): void
    {
        UserRepository::$related = ['posts'];

        parent::tearDown();
    }

    /**
     * @param  list<string>  $declared
     * @param  list<class-string<Model>>  $tables
     */
    #[Test]
    #[TestWith([['posts'], 'posts', [Post::class]], 'single level')]
    #[TestWith([['posts'], 'posts.comments', [Post::class, Comment::class]], 'nested requested with dot notation')]
    #[TestWith([['posts.comments'], 'posts.comments', [Post::class, Comment::class]], 'nested declared with dot notation')]
    public function related_is_eager_loaded(array $declared, string $requested, array $tables): void
    {
        UserRepository::$related = $declared;

        $this->seedUsersWithPostsAndComments();

        $this->recordQueries();

        $this->getJson(UserRepository::route(query: ['related' => $requested]))->assertOk();

        foreach ($tables as $model) {
            $this->assertQueryCountAgainst(1, $model);
        }
    }

    private function seedUsersWithPostsAndComments(): void
    {
        foreach (User::factory(3)->create() as $user) {
            foreach (Post::factory(2)->create(['user_id' => $user->id]) as $post) {
                Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
