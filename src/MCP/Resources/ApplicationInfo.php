<?php

namespace Binaryk\LaravelRestify\MCP\Resources;

use Laravel\Mcp\Server\Contracts\Resources\Content;
use Laravel\Mcp\Server\Resource;

class ApplicationInfo extends Resource
{
    public function description(): string
    {
        return 'Information about the Laravel Restify application and its capabilities.';
    }

    public function read(): string|Content
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

        return json_encode($context);
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
}
