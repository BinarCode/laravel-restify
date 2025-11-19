<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\MCP\Resources;

use Binaryk\LaravelRestify\Restify;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ApplicationInfo extends Resource
{
    /**
     * The resource's description.
     */
    protected string $description = 'Information about the Laravel Restify application and its capabilities, including repositories, version information, and system context.';

    /**
     * The resource's URI.
     */
    protected string $uri = 'file://instructions/application-info.md';

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'text/markdown';

    /**
     * Handle the resource request.
     */
    public function handle(): Response
    {
        $system = <<<'EOT'
You are an AI assistant integrated into Laravel Restify API system. You have access to:
- RESTful API repository management with automatic CRUD operations
- Advanced field types and relationships handling
- Dynamic filtering, searching, and sorting capabilities
- Action execution on resources
- Policy-based authorization
- API versioning and documentation
- Bulk operations support
- Custom endpoint routing
- Field validation and transformation

Provide helpful responses related to these features while maintaining API security and best practices.
EOT;

        $context = [
            'general_context' => $system,
            'application' => config('app.name'),
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'restify_version' => $this->getRestifyVersion(),
            'php_version' => PHP_VERSION,
            'debug_mode' => config('app.debug'),
            'api_prefix' => config('restify.base', '/api/restify'),
            'current_year' => now()->year,
            'current_month' => now()->month,
            'current_day' => now()->day,
            'current_hour' => now()->hour,
            'current_minute' => now()->minute,
            'repositories' => $this->getRepositoryMetadata(),
        ];

        return Response::json($context);
    }

    protected function getRestifyVersion(): string
    {
        $composerFile = base_path('composer.lock');

        if (! file_exists($composerFile)) {
            return 'Unknown';
        }

        $composerData = json_decode(file_get_contents($composerFile), true);

        foreach ($composerData['packages'] ?? [] as $package) {
            if ($package['name'] === 'binaryk/laravel-restify') {
                return $package['version'];
            }
        }

        return 'Unknown';
    }

    protected function getRepositoryMetadata(): array
    {
        return collect(Restify::$repositories)
            ->map(function (string $repository) {
                $instance = app($repository);

                return [
                    'name' => $repository,
                    'uri_key' => $instance->uriKey(),
                    'label' => $instance::label(),
                    'model' => $instance::guessModelClassName(),
                ];
            })
            ->values()
            ->toArray();
    }
}
