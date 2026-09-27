<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;

final class ServerGlobalsResetTest extends IntegrationTestCase
{
    private const string KEY = 'restify.tests.server-globals-reset';

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
    public function the_reset_before_a_test_does_not_rely_on_the_previous_tear_down(): void
    {
        $_SERVER[self::KEY] = 'left behind by a tearDown that threw';

        $this->resetServerGlobalsBeforeTest();

        $this->assertArrayNotHasKey(self::KEY, $_SERVER);
    }
}
