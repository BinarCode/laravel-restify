<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class FilterCommandTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    public function it_generates_an_advanced_filter_by_default(): void
    {
        $this->artisan('restify:filter', ['name' => 'Status'])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/Filters/StatusFilter.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\Filters;', $content);
        $this->assertStringContainsString('use Binaryk\LaravelRestify\Filters\AdvancedFilter;', $content);
        $this->assertStringContainsString('class StatusFilter extends AdvancedFilter', $content);
        $this->assertStringContainsString('public function filter(RestifyRequest $request, Builder|Relation $query, $value)', $content);
        $this->assertStringContainsString('public function rules(Request $request): array', $content);
    }

    #[Test]
    #[TestWith(['--sort', 'SortableFilter', 'Sortables'])]
    #[TestWith(['--search', 'SearchableFilter', 'Searchables'])]
    #[TestWith(['--match', 'MatchFilter', 'Matchers'])]
    #[TestWith(['--bool', 'BooleanFilter', 'Filters'])]
    #[TestWith(['--select', 'SelectFilter', 'Filters'])]
    #[TestWith(['--date', 'TimestampFilter', 'Filters'])]
    public function it_generates_the_filter_variant_matching_its_option(
        string $option,
        string $parentClass,
        string $namespaceSegment,
    ): void {
        $this->artisan('restify:filter', ['name' => 'Status', $option => true])
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/Restify/'.$namespaceSegment.'/StatusFilter.php';
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Restify\\'.$namespaceSegment.';', $content);
        $this->assertStringContainsString('use Binaryk\LaravelRestify\Filters\\'.$parentClass.';', $content);
        $this->assertStringContainsString('class StatusFilter extends '.$parentClass, $content);
    }

    #[Test]
    public function it_does_not_double_append_the_filter_suffix(): void
    {
        $this->artisan('restify:filter', ['name' => 'StatusFilter'])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/Restify/Filters/StatusFilter.php');
    }

    #[Test]
    public function it_refuses_to_overwrite_an_existing_filter_without_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Filters/StatusFilter.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:filter', ['name' => 'Status'])
            ->expectsOutputToContain('Filter already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    public function it_overwrites_an_existing_filter_with_force(): void
    {
        $path = $this->generatedAppPath.'/Restify/Filters/StatusFilter.php';
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan('restify:filter', ['name' => 'Status', '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString('class StatusFilter extends AdvancedFilter', File::get($path));
    }

    #[Test]
    public function it_rejects_a_reserved_php_name(): void
    {
        $this->artisan('restify:filter', ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/Restify/Filters');
    }
}
