<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositoryRoutePrefixAuthorizationTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        PostRepository::setPrefix(null);
        UserRepository::setPrefix(null);

        parent::tearDown();
    }

    #[Test]
    #[TestWith(['api/restify/posts', true], 'index')]
    #[TestWith(['api/restify/posts/1', true], 'show')]
    public function every_route_is_allowed_when_no_prefix_is_declared(string $path, bool $expected): void
    {
        $this->assertSame($expected, PostRepository::authorizedToUseRoute($this->requestFor($path)));
    }

    /**
     * The index branch used to be written separately from the rest; these pin that
     * both verbs answer the same way for the same path.
     */
    #[Test]
    #[TestWith(['api/v1/posts', true], 'index under the prefix')]
    #[TestWith(['api/v1/posts/1', true], 'show under the prefix')]
    #[TestWith(['api/restify/posts', false], 'index outside the prefix')]
    #[TestWith(['api/restify/posts/1', false], 'show outside the prefix')]
    #[TestWith(['api/v1', false], 'the prefix alone is not a repository route')]
    #[TestWith(['api/v1x/posts', false], 'a path that merely starts with the prefix text')]
    public function a_declared_prefix_admits_only_paths_beneath_it(string $path, bool $expected): void
    {
        PostRepository::setPrefix('api/v1');

        $this->assertSame($expected, PostRepository::authorizedToUseRoute($this->requestFor($path)));
    }

    #[Test]
    #[TestWith(['/api/v1'], 'leading slash')]
    #[TestWith(['api/v1/'], 'trailing slash')]
    #[TestWith(['/api/v1/'], 'both')]
    public function surrounding_slashes_do_not_change_what_the_prefix_admits(string $declared): void
    {
        PostRepository::setPrefix($declared);

        $this->assertTrue(PostRepository::authorizedToUseRoute($this->requestFor('api/v1/posts')));
        $this->assertFalse(PostRepository::authorizedToUseRoute($this->requestFor('api/restify/posts')));
    }

    #[Test]
    #[TestWith(['/'], 'a single slash')]
    #[TestWith([''], 'an empty string')]
    public function a_prefix_that_sanitizes_away_admits_everything(string $declared): void
    {
        PostRepository::setPrefix($declared);

        $this->assertSame('', PostRepository::prefix() ?? '');
        $this->assertTrue(PostRepository::authorizedToUseRoute($this->requestFor('api/restify/posts')));
    }

    #[Test]
    public function a_prefix_on_one_repository_leaves_another_alone(): void
    {
        PostRepository::setPrefix('api/v1');

        $this->assertFalse(PostRepository::authorizedToUseRoute($this->requestFor('api/restify/posts')));
        $this->assertTrue(UserRepository::authorizedToUseRoute($this->requestFor('api/restify/users')));
    }

    private function requestFor(string $path): RestifyRequest
    {
        return RestifyRequest::create('/'.ltrim($path, '/'), 'GET');
    }
}
