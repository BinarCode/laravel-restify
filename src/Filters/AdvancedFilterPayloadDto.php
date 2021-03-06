<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class AdvancedFilterPayloadDto
{
    public string $key;

    public ?array $value = null;

    public function __construct(array $payload)
    {
        $this->key = $payload['key'];
        $this->value = data_get($payload, 'value');
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
        return $this->value ?? [];
    }

    public function input(string $key, $default = null)
    {
        return data_get($this->value(), $key, $default);
    }
}
