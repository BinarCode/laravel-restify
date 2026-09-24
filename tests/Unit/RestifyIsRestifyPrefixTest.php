<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class RestifyIsRestifyPrefixTest extends IntegrationTestCase
{
    /** @var list<class-string<Repository>> */
    private array $originalRepositories;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRepositories = Restify::$repositories;
    }

    protected function tearDown(): void
    {
        Restify::$repositories = $this->originalRepositories;

        parent::tearDown();
    }

    #[Test]
    public function an_empty_string_prefix_is_excluded_and_does_not_falsely_match_the_root_path(): void
    {
        $repository = new class extends PostRepository
        {
            public static function prefix(): ?string
            {
                return '';
            }
        };

        Restify::$repositories = [$repository::class];

        $this->assertFalse(Restify::isRestify(Request::create('/')));
    }

    #[Test]
    public function a_zero_string_prefix_is_still_matched(): void
    {
        $repository = new class extends PostRepository
        {
            public static function prefix(): ?string
            {
                return '0';
            }
        };

        Restify::$repositories = [$repository::class];

        $this->assertTrue(Restify::isRestify(Request::create('/0/anything')));
        $this->assertFalse(Restify::isRestify(Request::create('/')));
    }

    #[Test]
    public function a_null_prefix_is_excluded_from_the_prefix_match(): void
    {
        $repository = new class extends PostRepository
        {
            public static function prefix(): ?string
            {
                return null;
            }
        };

        Restify::$repositories = [$repository::class];

        $this->assertFalse(Restify::isRestify(Request::create('/')));
    }
}
