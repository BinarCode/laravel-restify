<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Repository
 */
trait McpStoreTool
{
    public function storeTool(McpStoreRequest $request): array
    {
        $this->collectFields($request)
            ->forStore($request, $this)
            ->areFiles()
            ->each(function (File $file) use ($request) {
                if (! $request->has($file->attribute)) {
                    return;
                }

                $value = $request->input($file->attribute);

                if (! is_string($value) || $value === '') {
                    return;
                }

                $uploadedFile = $this->resolveUploadedFileFromInput($value);

                if ($uploadedFile) {
                    $request->merge([$file->attribute => $uploadedFile]);
                }
            });

        return $this
            ->allowToStore($request)
            ->store($request)
            ->getData(true);
    }

    public function resolveUploadedFileFromInput(string $value): ?UploadedFile
    {
        if (config('restify.mcp.files.allow_local_paths') && is_file($value) && is_readable($value)) {
            return new UploadedFile($value, basename($value), mime_content_type($value) ?: null, null, true);
        }

        if (! static::isSafePublicUrl($value)) {
            return null;
        }

        $contents = static::fetchRemoteFile($value);

        if ($contents === null) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'restify_mcp_upload_');
        file_put_contents($tempPath, $contents);

        $fileName = basename((string) parse_url($value, PHP_URL_PATH)) ?: 'upload';

        return new UploadedFile($tempPath, $fileName, mime_content_type($tempPath) ?: null, null, true);
    }

    public static function isSafePublicUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = trim($parts['host'], '[]');

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    protected static function fetchRemoteFile(string $url): ?string
    {
        $maxBytes = (int) config('restify.mcp.files.max_bytes', 10 * 1024 * 1024);
        $timeout = (int) config('restify.mcp.files.timeout', 10);

        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'follow_location' => 0,
            ],
        ]);

        $contents = @file_get_contents($url, false, $context, 0, $maxBytes + 1);

        if ($contents === false) {
            return null;
        }

        if (strlen($contents) > $maxBytes) {
            return null;
        }

        return $contents;
    }

    public static function storeToolSchema(JsonSchema $schema): array
    {
        $repository = static::resolveWith(static::newModel());
        $request = app(McpStoreRequest::class);

        $properties = [];

        // Use MCP-specific fields when available
        $fields = method_exists($repository, 'fieldsForMcpStore')
            ? collect($repository->fieldsForMcpStore($request))
            : $repository->collectFields($request)
                ->forStore($request, $repository)
                ->withoutActions($request, $repository);

        $fields->each(function (Field $field) use ($schema, $repository, &$properties, $request) {
            $fieldSchema = $field->resolveJsonSchema($schema, $request, $repository)->jsonSchema();

            if ($fieldSchema !== null) {
                $properties[$field->attribute] = $fieldSchema;
            }
        });

        return $properties;
    }
}
