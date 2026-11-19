<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\MCP\Files\RemoteFileFetcher;
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

        // A first gate, kept so that an override of it still applies. The download does not rely on it:
        // fetchRemoteFile() resolves the host itself, validates those addresses and connects to them.
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

    /**
     * Determine if the URL is an http or https URL whose host resolves to public addresses only.
     *
     * This is a first gate. What keeps the download safe is fetchRemoteFile(), which validates the
     * addresses it resolves itself and connects to those.
     */
    public static function isSafePublicUrl(string $url): bool
    {
        return app(RemoteFileFetcher::class)->pin($url) !== null;
    }

    /**
     * Download the file, or return null when the URL is refused or the download fails.
     */
    protected static function fetchRemoteFile(string $url): ?string
    {
        return app(RemoteFileFetcher::class)->fetch(
            $url,
            (int) config('restify.mcp.files.max_bytes', 10 * 1024 * 1024),
            (int) config('restify.mcp.files.timeout', 10),
        );
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
