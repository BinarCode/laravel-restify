<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Arr;

class AdvancedFilterPayloadDto
{
    public string $key;

    public ?array $value = null;

    public function __construct(array $payload)
    {
        $this->key = $payload['key'];
        $this->value = Arr::wrap(data_get($payload, 'value'));
    }

    public static function makeFromRequest(RestifyRequest $request, string $key): self
    {
        $payload = json_decode(base64_decode($request->query('filters')), true);

        return new static(
            collect($payload)->firstWhere('key', $key)
        );
    }

    public function value(): array
    {
        $value = $this->value;

        if (is_string($this->value)) {
            $value = Arr::wrap($value);
        }

        return $value ?? [];
    }

    public function input(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return Arr::first($this->value());
        }

        return data_get($this->value(), $key, $default);
    }
}
