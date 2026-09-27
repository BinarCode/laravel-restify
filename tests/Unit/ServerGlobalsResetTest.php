<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;

final class ServerGlobalsResetTest extends IntegrationTestCase
{
    private const string KEY = 'restify.tests.server-globals-reset';

    private const string AFTER_TEAR_DOWN_KEY = 'restify.tests.server-globals-reset.after-tear-down';

    #[Test]
    public function a_test_sets_a_server_key_without_cleaning_it_up(): void
    {
        $_SERVER[self::KEY] = 'left behind';

        $this->assertSame('left behind', $_SERVER[self::KEY]);
    }

    #[Test]
    #[Depends('a_test_sets_a_server_key_without_cleaning_it_up')]
    public function the_next_test_starts_without_that_key(): void
    {
        $this->assertArrayNotHasKey(self::KEY, $_SERVER);
    }

    #[Test]
    public function a_test_whose_tear_down_leaves_a_key_behind(): void
    {
        $this->assertArrayNotHasKey(self::AFTER_TEAR_DOWN_KEY, $_SERVER);
    }

    #[Test]
    #[Depends('a_test_whose_tear_down_leaves_a_key_behind')]
    public function the_next_test_still_starts_without_that_key(): void
    {
        $this->assertArrayNotHasKey(self::AFTER_TEAR_DOWN_KEY, $_SERVER);
    }

    #[After]
    protected function leaveAKeyBehindAfterTearDown(): void
    {
        if ($this->name() === 'a_test_whose_tear_down_leaves_a_key_behind') {
            $_SERVER[self::AFTER_TEAR_DOWN_KEY] = 'set after tearDown finished';
        }
    }
}
