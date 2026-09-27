<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyWithExtraSyncedStaffRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\ActivateAction;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;

final class StaticPropertiesResetTest extends IntegrationTestCase
{
    #[After]
    protected function leaveAStaticBehindAfterTheTest(): void
    {
        if ($this->name() === 'a_test_whose_after_hook_leaves_a_static_behind') {
            ActivateAction::$applied = ['set after tearDown finished'];
        }
    }

    #[Test]
    public function a_test_changes_statics_without_resetting_them(): void
    {
        Restify::repositories([CompanyWithExtraSyncedStaffRepository::class]);
        PostRepository::$search = ['title'];
        PostRepository::setPrefix('api/v1');
        CompanyRepository::$attachers = ['users' => static fn (): null => null];
        PublishPostAction::$applied = ['left behind'];

        $this->assertContains(CompanyWithExtraSyncedStaffRepository::class, Restify::$repositories);
        $this->assertSame('api/v1', PostRepository::prefix());
        $this->assertArrayHasKey('users', Repository::$attachers);
    }

    #[Test]
    #[Depends('a_test_changes_statics_without_resetting_them')]
    public function the_next_test_starts_from_their_defaults(): void
    {
        $this->assertNotContains(CompanyWithExtraSyncedStaffRepository::class, Restify::$repositories);
        $this->assertContains(PostRepository::class, Restify::$repositories);
        $this->assertSame(['id', 'title'], PostRepository::$search);
        $this->assertNull(PostRepository::prefix());
        $this->assertNull(CompanyRepository::prefix());
        $this->assertSame([], Repository::$attachers);
        $this->assertSame([], PublishPostAction::$applied);
    }

    #[Test]
    public function a_test_whose_after_hook_leaves_a_static_behind(): void
    {
        $this->assertSame([], ActivateAction::$applied);
    }

    #[Test]
    #[Depends('a_test_whose_after_hook_leaves_a_static_behind')]
    public function the_next_test_still_starts_from_the_default(): void
    {
        $this->assertSame([], ActivateAction::$applied);
    }
}
