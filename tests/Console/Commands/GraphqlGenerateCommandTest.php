<?php

namespace Binaryk\LaravelRestify\Tests\Console\Commands;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Filesystem\Filesystem;

class GraphqlGenerateCommandTest extends IntegrationTestCase
{
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);

        // Clean up any previous test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files after each test
        $this->cleanupTestFiles();

        parent::tearDown();
    }

    public function test_can_generate_graphql_schema(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--force' => true,
            '--skip-preview' => true,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('✅ Schema generated: '.$outputPath.'/schema.graphql')
            ->expectsOutput('🎉 GraphQL generation complete!')
            ->assertExitCode(0);

        // Assert schema file was created
        $this->assertFileExists($outputPath.'/schema.graphql');

        // Assert schema contains expected content
        $schemaContent = $this->files->get($outputPath.'/schema.graphql');
        $this->assertStringContainsString('# Auto-generated GraphQL schema from Restify repositories', $schemaContent);
        $this->assertStringContainsString('type Query {', $schemaContent);
        $this->assertStringContainsString('type Mutation {', $schemaContent);
    }

    public function test_can_generate_graphql_schema_with_resolvers(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--resolvers' => true,
            '--force' => true,
            '--skip-preview' => true,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('✅ Schema generated: '.$outputPath.'/schema.graphql')
            ->expectsOutputToContain('resolver classes')
            ->assertExitCode(0);

        // Assert schema file was created
        $this->assertFileExists($outputPath.'/schema.graphql');

        // Assert resolvers directory was created
        $this->assertDirectoryExists($outputPath.'/Resolvers');

        // Check for some expected resolver files (based on test repositories)
        $resolverFiles = $this->files->files($outputPath.'/Resolvers');
        $this->assertNotEmpty($resolverFiles);

        // Check resolver file content
        if (count($resolverFiles) > 0) {
            $resolverContent = $this->files->get($resolverFiles[0]->getPathname());
            $this->assertStringContainsString('namespace App\\GraphQL\\Resolvers;', $resolverContent);
            $this->assertStringContainsString('public function show', $resolverContent);
            $this->assertStringContainsString('public function index', $resolverContent);
            $this->assertStringContainsString('public function create', $resolverContent);
            $this->assertStringContainsString('public function update', $resolverContent);
            $this->assertStringContainsString('public function delete', $resolverContent);
        }
    }

    public function test_fails_gracefully_when_no_repositories_found(): void
    {
        // Temporarily modify the repositories array to simulate no repositories
        $originalRepositories = Restify::$repositories;
        Restify::$repositories = [];

        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--force' => true,
            '--skip-preview' => true,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('No repositories found. Make sure you have repositories in your app/Restify directory.')
            ->assertExitCode(1);

        // Restore original repositories
        Restify::$repositories = $originalRepositories;
    }

    public function test_can_use_custom_schema_file_name(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');
        $schemaFile = 'custom-schema.graphql';

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--schema-file' => $schemaFile,
            '--force' => true,
            '--skip-preview' => true,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('✅ Schema generated: '.$outputPath.'/'.$schemaFile)
            ->assertExitCode(0);

        // Assert custom schema file was created
        $this->assertFileExists($outputPath.'/'.$schemaFile);
    }

    public function test_shows_preview_and_waits_for_confirmation(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--force' => true,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('📋 Preview of files to be generated:')
            ->expectsOutputToContain('🔍 Found')
            ->expectsOutputToContain('📂 Output configuration:')
            ->expectsOutputToContain('📄 Files that will be generated:')
            ->expectsOutputToContain('📝 Sample GraphQL schema preview:')
            ->expectsQuestion('Do you want to proceed with generating these files?', true)
            ->expectsOutput('Generating GraphQL files...')
            ->expectsOutput('✅ Schema generated: '.$outputPath.'/schema.graphql')
            ->assertExitCode(0);

        // Assert files were created
        $this->assertFileExists($outputPath.'/schema.graphql');
    }

    public function test_can_cancel_generation_from_preview(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
        ])
            ->expectsOutput('🚀 Generating GraphQL schema from Restify repositories...')
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('📋 Preview of files to be generated:')
            ->expectsQuestion('Do you want to proceed with generating these files?', false)
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        // Assert no files were created
        $this->assertFileDoesNotExist($outputPath.'/schema.graphql');
    }

    public function test_prompts_for_overwrite_when_files_exist(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');
        $schemaFile = 'schema.graphql';

        // Create directory and file first
        $this->files->makeDirectory($outputPath, 0755, true);
        $this->files->put($outputPath.'/'.$schemaFile, 'existing content');

        // First run without force should prompt
        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--skip-preview' => true,
        ])
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsQuestion('File '.$outputPath.'/'.$schemaFile.' already exists. Overwrite?', false)
            ->assertExitCode(0);

        // Assert file content wasn't changed
        $content = $this->files->get($outputPath.'/'.$schemaFile);
        $this->assertEquals('existing content', $content);
    }

    public function test_includes_helpful_next_steps_output(): void
    {
        $outputPath = base_path('tests/temp/GraphQL');

        $this->artisan('restify:graphql:generate', [
            '--output-path' => $outputPath,
            '--force' => true,
            '--skip-preview' => true,
        ])
            ->expectsOutput('✅ Authentication mocking enabled for console context')
            ->expectsOutput('Next steps:')
            ->expectsOutput('  1. Install lighthouse/lighthouse if not already installed')
            ->expectsOutput('  2. Configure lighthouse to use the generated schema')
            ->expectsOutput('  3. Register the resolvers in your GraphQL setup')
            ->assertExitCode(0);
    }

    protected function cleanupTestFiles(): void
    {
        $testPath = base_path('tests/temp');

        if ($this->files->exists($testPath)) {
            $this->files->deleteDirectory($testPath);
        }
    }
}
