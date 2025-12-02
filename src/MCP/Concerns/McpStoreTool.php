<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
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

                $filePath = $request->input($file->attribute);
                $actualPath = null;
                $fileName = null;

                if (file_exists($filePath) && is_readable($filePath)) {
                    $actualPath = $filePath;
                    $fileName = basename($filePath);
                } elseif (filter_var($filePath, FILTER_VALIDATE_URL)) {
                    $actualPath = tempnam(sys_get_temp_dir(), 'upload_');
                    file_put_contents($actualPath, file_get_contents($filePath));
                    $fileName = basename(parse_url($filePath, PHP_URL_PATH));
                }

                if ($actualPath) {
                    $uploadedFile = new UploadedFile(
                        $actualPath,
                        $fileName,
                        mime_content_type($actualPath),
                        null,
                        true // Mark it as test mode to allow local files
                    );

                    $request->merge([$file->attribute => $uploadedFile]);
                }
            });

        return $this
            ->allowToStore($request)
            ->store($request)
            ->getData(true);
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
