<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithGeneratedApp;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ActionCommand, FilterCommand, GetterCommand, McpResourceCommand, StoreCommand and
 * ToolCommand share the same generate/suffix/exists/force/reserved behaviour from
 * Illuminate\Console\GeneratorCommand. This matrix covers that shared behaviour once;
 * FilterCommand's own filter-variant options are covered separately below.
 */
class GeneratorCommandsTest extends IntegrationTestCase
{
    use InteractsWithGeneratedApp;

    #[Test]
    #[TestWith(['restify:action', 'PublishPost', 'Restify/Actions/PublishPostAction.php', 'namespace App\Restify\Actions;', 'class PublishPostAction extends Action', 'public function handle(ActionRequest $request, Collection $models): JsonResponse', 'Action'], 'action')]
    #[TestWith(['restify:getter', 'ExportPosts', 'Restify/Getters/ExportPostsGetter.php', 'namespace App\Restify\Getters;', 'class ExportPostsGetter extends Getter', 'public function handle(GetterRequest $request): JsonResponse', 'Getter'], 'getter')]
    #[TestWith(['restify:store', 'Avatar', 'Restify/Stores/AvatarStore.php', 'namespace App\Restify\Stores;', 'class AvatarStore implements Storable', 'public function handle(Request $request, Model $model, $attribute): array', 'Matcher'], 'store')]
    #[TestWith(['restify:mcp-tool', 'SendEmail', 'Restify/Mcp/Tools/SendEmailTool.php', 'namespace App\Restify\Mcp\Tools;', 'class SendEmailTool extends Tool', "return 'send-email';", 'Tool'], 'mcp-tool')]
    #[TestWith(['restify:mcp-resource', 'Changelog', 'Restify/Mcp/Resources/ChangelogResource.php', 'namespace App\Restify\Mcp\Resources;', 'class ChangelogResource extends Resource', 'public function read(): string|Content', 'Resource'], 'mcp-resource')]
    #[TestWith(['restify:filter', 'Status', 'Restify/Filters/StatusFilter.php', 'namespace App\Restify\Filters;', 'class StatusFilter extends AdvancedFilter', 'public function filter(RestifyRequest $request, Builder|Relation $query, $value)', 'Filter'], 'filter')]
    public function it_generates_the_class(
        string $command,
        string $bareName,
        string $relativePath,
        string $namespaceLine,
        string $classLine,
        string $extraLine,
        string $typeLabel,
    ): void {
        $this->artisan($command, ['name' => $bareName])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $path = $this->generatedAppPath.'/'.$relativePath;
        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString($namespaceLine, $content);
        $this->assertStringContainsString($classLine, $content);
        $this->assertStringContainsString($extraLine, $content);
    }

    #[Test]
    #[TestWith(['restify:action', 'Restify/Actions/PublishPostAction.php'], 'action')]
    #[TestWith(['restify:getter', 'Restify/Getters/ExportPostsGetter.php'], 'getter')]
    #[TestWith(['restify:store', 'Restify/Stores/AvatarStore.php'], 'store')]
    #[TestWith(['restify:mcp-tool', 'Restify/Mcp/Tools/SendEmailTool.php'], 'mcp-tool')]
    #[TestWith(['restify:mcp-resource', 'Restify/Mcp/Resources/ChangelogResource.php'], 'mcp-resource')]
    #[TestWith(['restify:filter', 'Restify/Filters/StatusFilter.php'], 'filter')]
    public function it_does_not_double_append_the_suffix(string $command, string $relativePath): void
    {
        $fullName = pathinfo($relativePath, PATHINFO_FILENAME);

        $this->artisan($command, ['name' => $fullName])
            ->assertExitCode(0);

        $this->assertFileExists($this->generatedAppPath.'/'.$relativePath);
    }

    #[Test]
    #[TestWith(['restify:action', 'PublishPost', 'Restify/Actions/PublishPostAction.php', 'Action'], 'action')]
    #[TestWith(['restify:getter', 'ExportPosts', 'Restify/Getters/ExportPostsGetter.php', 'Getter'], 'getter')]
    #[TestWith(['restify:store', 'Avatar', 'Restify/Stores/AvatarStore.php', 'Matcher'], 'store')]
    #[TestWith(['restify:mcp-tool', 'SendEmail', 'Restify/Mcp/Tools/SendEmailTool.php', 'Tool'], 'mcp-tool')]
    #[TestWith(['restify:mcp-resource', 'Changelog', 'Restify/Mcp/Resources/ChangelogResource.php', 'Resource'], 'mcp-resource')]
    #[TestWith(['restify:filter', 'Status', 'Restify/Filters/StatusFilter.php', 'Filter'], 'filter')]
    public function it_refuses_to_overwrite_an_existing_class_without_force(
        string $command,
        string $bareName,
        string $relativePath,
        string $typeLabel,
    ): void {
        $path = $this->generatedAppPath.'/'.$relativePath;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan($command, ['name' => $bareName])
            ->expectsOutputToContain($typeLabel.' already exists.')
            ->assertExitCode(0);

        $this->assertSame('original content', File::get($path));
    }

    #[Test]
    #[TestWith(['restify:action', 'PublishPost', 'Restify/Actions/PublishPostAction.php', 'class PublishPostAction extends Action'], 'action')]
    #[TestWith(['restify:getter', 'ExportPosts', 'Restify/Getters/ExportPostsGetter.php', 'class ExportPostsGetter extends Getter'], 'getter')]
    #[TestWith(['restify:store', 'Avatar', 'Restify/Stores/AvatarStore.php', 'class AvatarStore implements Storable'], 'store')]
    #[TestWith(['restify:mcp-tool', 'SendEmail', 'Restify/Mcp/Tools/SendEmailTool.php', 'class SendEmailTool extends Tool'], 'mcp-tool')]
    #[TestWith(['restify:mcp-resource', 'Changelog', 'Restify/Mcp/Resources/ChangelogResource.php', 'class ChangelogResource extends Resource'], 'mcp-resource')]
    #[TestWith(['restify:filter', 'Status', 'Restify/Filters/StatusFilter.php', 'class StatusFilter extends AdvancedFilter'], 'filter')]
    public function it_overwrites_an_existing_class_with_force(
        string $command,
        string $bareName,
        string $relativePath,
        string $classLine,
    ): void {
        $path = $this->generatedAppPath.'/'.$relativePath;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'original content');

        $this->artisan($command, ['name' => $bareName, '--force' => true])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $this->assertStringContainsString($classLine, File::get($path));
    }

    #[Test]
    #[TestWith(['restify:action', 'Restify/Actions'], 'action')]
    #[TestWith(['restify:getter', 'Restify/Getters'], 'getter')]
    #[TestWith(['restify:store', 'Restify/Stores'], 'store')]
    #[TestWith(['restify:mcp-tool', 'Restify/Mcp/Tools'], 'mcp-tool')]
    #[TestWith(['restify:mcp-resource', 'Restify/Mcp/Resources'], 'mcp-resource')]
    #[TestWith(['restify:filter', 'Restify/Filters'], 'filter')]
    public function it_rejects_a_reserved_php_name(string $command, string $relativeDirectory): void
    {
        $this->artisan($command, ['name' => 'class'])
            ->expectsOutputToContain('reserved by PHP')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->generatedAppPath.'/'.$relativeDirectory);
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
}
