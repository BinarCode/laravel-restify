<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Base64File extends File
{
    public function fillAttribute(RestifyRequest $request, $model, ?int $bulkRow = null)
    {
        if ($this->storeCallback instanceof Closure) {
            return call_user_func($this->storeCallback, $request, $model, $this->attribute);
        }

        // Delegate to parent for regular file uploads first
        if ($this->resolveFileFromRequest($request)) {
            return parent::fillAttribute($request, $model, $bulkRow);
        }

        $input = $request->input($this->attribute);

        if (! $input || ! is_string($input)) {
            return $this;
        }

        // Delegate to parent for URL inputs
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            return parent::fillAttribute($request, $model, $bulkRow);
        }

        // Handle base64 data
        if (! $this->isBase64($input)) {
            return $this;
        }

        if ($this->isPrunable()) {
            call_user_func(
                $this->deleteCallback,
                $request,
                $model,
                $this->getStorageDisk(),
                $this->getStoragePath()
            );
        }

        $result = $this->mergeExtraStorageColumnsForBase64($request, [
            $this->attribute => $this->storeBase64File($request),
        ]);

        if (! is_array($result)) {
            return $model->{$this->attribute} = $result;
        }

        foreach ($result as $key => $value) {
            if ($model->isFillable($key)) {
                $model->{$key} = $value;
            }
        }

        return $this;
    }

    protected function storeBase64File(RestifyRequest $request): string
    {
        $base64Data = $request->input($this->attribute);
        $imageData = $this->decodeBase64($base64Data);
        $extension = $this->detectExtension($base64Data);

        $filename = $this->resolveFilename($request, $extension);
        $directory = trim($this->getStorageDir(), '/');
        $path = $directory ? "{$directory}/{$filename}" : $filename;

        Storage::disk($this->getStorageDisk())->put($path, $imageData);

        return $path;
    }

    protected function resolveFilename(RestifyRequest $request, string $extension): string
    {
        if (! $this->storeAs) {
            $this->customFilename = null;
            $this->useCustomFilenameForOriginal = false;

            return Str::uuid().'.'.$extension;
        }

        $isCallable = is_callable($this->storeAs);
        $filename = $isCallable
            ? call_user_func($this->storeAs, $request)
            : $this->storeAs;

        if (empty($filename)) {
            $this->customFilename = null;
            $this->useCustomFilenameForOriginal = false;

            return Str::uuid().'.'.$extension;
        }

        // Smart extension handling - append if missing
        if ($extension && ! str_ends_with(strtolower($filename), '.'.$extension)) {
            $filename = $filename.'.'.$extension;
        }

        $this->customFilename = $filename;
        $this->useCustomFilenameForOriginal = $isCallable;

        return $filename;
    }

    protected function mergeExtraStorageColumnsForBase64(RestifyRequest $request, array $attributes): array
    {
        $base64Data = $request->input($this->attribute);

        if ($this->originalNameColumn) {
            $attributes[$this->originalNameColumn] = ($this->useCustomFilenameForOriginal && $this->customFilename)
                ? $this->customFilename
                : $this->detectOriginalName($base64Data);
        }

        if ($this->sizeColumn) {
            $attributes[$this->sizeColumn] = $this->calculateDecodedSize($base64Data);
        }

        return $attributes;
    }

    protected function detectOriginalName(string $base64Data): string
    {
        $extension = $this->detectExtension($base64Data);

        return 'base64-upload.'.$extension;
    }

    protected function calculateDecodedSize(string $base64Data): int
    {
        $parts = explode(',', $base64Data);
        $encoded = count($parts) > 1 ? $parts[1] : $parts[0];

        return (int) (strlen($encoded) * 3 / 4);
    }

    protected function isBase64(string $data): bool
    {
        // Check for data URI format
        if (str_starts_with($data, 'data:')) {
            return true;
        }

        // Check for raw base64 (no data URI prefix)
        return base64_encode(base64_decode($data, true)) === $data;
    }

    protected function decodeBase64(string $base64Data): string
    {
        $parts = explode(',', $base64Data);

        return base64_decode(count($parts) > 1 ? $parts[1] : $parts[0]);
    }

    protected function detectExtension(string $base64Data): string
    {
        return match (true) {
            str_contains($base64Data, 'image/png') => 'png',
            str_contains($base64Data, 'image/jpeg'), str_contains($base64Data, 'image/jpg') => 'jpg',
            str_contains($base64Data, 'image/gif') => 'gif',
            str_contains($base64Data, 'image/webp') => 'webp',
            str_contains($base64Data, 'image/svg+xml'), str_contains($base64Data, 'image/svg') => 'svg',
            str_contains($base64Data, 'application/pdf') => 'pdf',
            str_contains($base64Data, 'text/plain') => 'txt',
            str_contains($base64Data, 'application/json') => 'json',
            default => 'bin',
        };
    }
}
