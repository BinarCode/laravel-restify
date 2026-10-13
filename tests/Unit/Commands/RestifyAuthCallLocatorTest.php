<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit\Commands;

use Binaryk\LaravelRestify\Commands\Support\RestifyAuthCallLocator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class RestifyAuthCallLocatorTest extends TestCase
{
    #[Test]
    #[TestWith(["<?php\nRoute::restifyAuth();", true], 'bare call')]
    #[TestWith(["<?php\n\\Route::restifyAuth();", true], 'fully qualified call')]
    #[TestWith(["<?php\nRoute /* c */ :: // c\n restifyAuth /* c */ (['login']);", true], 'whitespace and comments between tokens')]
    #[TestWith(["<?php\nRoute::restifyAuth()->name('x')", true], 'call without a terminating semicolon')]
    #[TestWith(["<?php\n// Route::restifyAuth();", false], 'line comment')]
    #[TestWith(["<?php\n/* Route::restifyAuth(); */", false], 'block comment')]
    #[TestWith(["<?php\n\$x = 'Route::restifyAuth();';", false], 'string')]
    #[TestWith(["<?php\n\$x = <<<TXT\nRoute::restifyAuth();\nTXT;\n", false], 'heredoc')]
    #[TestWith(["<?php\nOther::restifyAuth();", false], 'other class')]
    #[TestWith(["<?php\nRoute::restifyAuthenticated();", false], 'other method')]
    #[TestWith(["<?php\nRoute::restifyAuth;", false], 'no opening parenthesis')]
    #[TestWith(["<?php\nRoute::", false], 'truncated after the scope operator')]
    public function it_detects_only_a_real_call(string $contents, bool $expected): void
    {
        $this->assertSame($expected, (new RestifyAuthCallLocator)->has($contents));
    }

    #[Test]
    public function it_locates_the_statement_offset_length_and_arguments(): void
    {
        $contents = "<?php\n\$a = 1; Route::restifyAuth(['login', 'register']); \$b = 2;";

        $calls = (new RestifyAuthCallLocator)->locate($contents);

        $this->assertCount(1, $calls);
        $this->assertSame("['login', 'register']", $calls[0]['arguments']);
        $this->assertSame(
            "Route::restifyAuth(['login', 'register']);",
            substr($contents, $calls[0]['offset'], $calls[0]['length'])
        );
    }

    #[Test]
    public function it_balances_nested_parentheses_in_the_arguments(): void
    {
        $calls = (new RestifyAuthCallLocator)->locate("<?php\nRoute::restifyAuth(array_merge(['a'], foo()));");

        $this->assertSame("array_merge(['a'], foo())", $calls[0]['arguments']);
    }

    #[Test]
    public function it_locates_a_fully_qualified_call_from_the_leading_backslash(): void
    {
        $contents = "<?php\n\\Route::restifyAuth();";

        $calls = (new RestifyAuthCallLocator)->locate($contents);

        $this->assertSame('\Route::restifyAuth();', substr($contents, $calls[0]['offset'], $calls[0]['length']));
    }

    #[Test]
    public function it_locates_every_call(): void
    {
        $calls = (new RestifyAuthCallLocator)->locate("<?php\nRoute::restifyAuth();\n\\Route::restifyAuth(['login']);");

        $this->assertCount(2, $calls);
        $this->assertSame('', $calls[0]['arguments']);
        $this->assertSame("['login']", $calls[1]['arguments']);
    }

    #[Test]
    #[TestWith(["<?php\nRoute::restifyAuth()"], 'unterminated statement')]
    #[TestWith(["<?php\nRoute::restifyAuth(['login']"], 'unbalanced parentheses')]
    #[TestWith(["<?php\n// Route::restifyAuth();"], 'comment')]
    public function it_ignores_what_is_not_a_complete_statement(string $contents): void
    {
        $this->assertSame([], (new RestifyAuthCallLocator)->locate($contents));
    }

    #[Test]
    public function it_reports_the_last_closing_tag_offset(): void
    {
        $locator = new RestifyAuthCallLocator;

        $this->assertNull($locator->lastCloseTagOffset("<?php\nRoute::restifyAuth();"));
        $this->assertSame(
            strlen("<?php echo 1; ?>\n<?php echo 2; "),
            $locator->lastCloseTagOffset("<?php echo 1; ?>\n<?php echo 2; ?>")
        );
    }
}
