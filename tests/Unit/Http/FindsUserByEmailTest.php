<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Http;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\FindsUserByEmail;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class FindsUserByEmailTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['Old@Example.com', 'Old@Example.com'])]
    #[TestWith(['old@example.com', 'Old@Example.com'])]
    #[TestWith(['OLD@EXAMPLE.COM', 'Old@Example.com'])]
    public function it_accepts_a_row_whose_email_matches_ignoring_case(string $stored, string $given): void
    {
        $this->assertSame($stored, $this->match([$stored], $given)?->getAttribute('email'));
    }

    #[Test]
    public function it_prefers_the_exact_row_over_a_case_variant(): void
    {
        $this->assertSame('Old@Example.com', $this->match(['old@example.com', 'Old@Example.com'], 'Old@Example.com')?->getAttribute('email'));
    }

    #[Test]
    public function it_refuses_a_row_an_accent_insensitive_collation_matched_to_a_different_email(): void
    {
        $this->assertNull($this->match(['victim@example.com'], 'víctim@example.com'));
    }

    /**
     * @param  list<string>  $storedEmails
     */
    private function match(array $storedEmails, string $email): ?Model
    {
        $users = Collection::make($storedEmails)
            ->map(static fn (string $stored): User => (new User)->forceFill(['email' => $stored]));

        $finder = new class
        {
            use FindsUserByEmail;

            /**
             * @param  Collection<int, User>  $users
             */
            public function pick(Collection $users, string $email): ?Model
            {
                return self::userMatchingEmail($users, $email);
            }
        };

        return $finder->pick($users, $email);
    }
}
