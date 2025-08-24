<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Illuminate\Validation\Rule;

trait ValidationMethods
{
    /**
     * Mark field as required.
     *
     * @return $this
     */
    public function required(): self
    {
        $this->rules('required');

        return $this;
    }

    /**
     * Mark field as nullable.
     *
     * @return $this
     */
    public function nullable(): self
    {
        $this->rules('nullable');

        return $this;
    }

    /**
     * Validate field as date.
     *
     * @return $this
     */
    public function date(): self
    {
        $this->rules('date');

        return $this;
    }

    /**
     * Validate field as datetime.
     *
     * @return $this
     */
    public function datetime(): self
    {
        $this->rules('date');

        return $this;
    }

    /**
     * Validate field as email.
     *
     * @return $this
     */
    public function email(): self
    {
        $this->rules('email');

        return $this;
    }

    /**
     * Validate field as numeric.
     *
     * @return $this
     */
    public function numeric(): self
    {
        $this->rules('numeric');

        return $this;
    }

    /**
     * Validate field as integer.
     *
     * @return $this
     */
    public function integer(): self
    {
        $this->rules('integer');

        return $this;
    }

    /**
     * Validate field as boolean.
     *
     * @return $this
     */
    public function boolean(): self
    {
        $this->rules('boolean');

        return $this;
    }

    /**
     * Validate field as string.
     *
     * @return $this
     */
    public function string(): self
    {
        $this->rules('string');

        return $this;
    }

    /**
     * Validate field as array.
     *
     * @return $this
     */
    public function array(): self
    {
        $this->rules('array');

        return $this;
    }

    /**
     * Validate field as URL.
     *
     * @return $this
     */
    public function url(): self
    {
        $this->rules('url');

        return $this;
    }

    /**
     * Validate field as UUID.
     *
     * @return $this
     */
    public function uuid(): self
    {
        $this->rules('uuid');

        return $this;
    }

    /**
     * Validate field as IP address.
     *
     * @return $this
     */
    public function ip(): self
    {
        $this->rules('ip');

        return $this;
    }

    /**
     * Validate field as IPv4 address.
     *
     * @return $this
     */
    public function ipv4(): self
    {
        $this->rules('ipv4');

        return $this;
    }

    /**
     * Validate field as IPv6 address.
     *
     * @return $this
     */
    public function ipv6(): self
    {
        $this->rules('ipv6');

        return $this;
    }

    /**
     * Set minimum value/length for field.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function min($value): self
    {
        $this->rules("min:$value");

        return $this;
    }

    /**
     * Set maximum value/length for field.
     *
     * @param  int|float  $value
     * @return $this
     */
    public function max($value): self
    {
        $this->rules("max:$value");

        return $this;
    }

    /**
     * Set field value/length between min and max.
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return $this
     */
    public function between($min, $max): self
    {
        $this->rules("between:$min,$max");

        return $this;
    }

    /**
     * Validate field as unique.
     *
     * @param  string  $table
     * @param  string|null  $column
     * @param  string|null  $except
     * @param  string  $idColumn
     * @return $this
     */
    public function uniqueRule($table, $column = null, $except = null, $idColumn = 'id'): self
    {
        $column = $column ?? $this->attribute;

        if ($except) {
            $this->rules(Rule::unique($table, $column)->ignore($except, $idColumn));
        } else {
            $this->rules("unique:$table,$column");
        }

        return $this;
    }

    /**
     * Validate field exists in table.
     *
     * @param  string  $table
     * @param  string|null  $column
     * @return $this
     */
    public function exists($table, $column = null): self
    {
        $column = $column ?? 'id';
        $this->rules("exists:$table,$column");

        return $this;
    }

    /**
     * Field must be confirmed (field_confirmation must exist).
     *
     * @return $this
     */
    public function confirmed(): self
    {
        $this->rules('confirmed');

        return $this;
    }

    /**
     * Apply password validation rules.
     *
     * @param  int  $min
     * @return $this
     */
    public function password($min = 8): self
    {
        $this->rules('string', "min:$min");

        return $this;
    }

    /**
     * Validate field with regex pattern.
     *
     * @param  string  $pattern
     * @return $this
     */
    public function regex($pattern): self
    {
        $this->rules("regex:$pattern");

        return $this;
    }

    /**
     * Set exact size validation.
     *
     * @param  int  $value
     * @return $this
     */
    public function size($value): self
    {
        $this->rules("size:$value");

        return $this;
    }

    /**
     * Field must be accepted (yes, on, 1, or true).
     *
     * @return $this
     */
    public function accepted(): self
    {
        $this->rules('accepted');

        return $this;
    }

    /**
     * Field must be a valid JSON string.
     *
     * @return $this
     */
    public function json(): self
    {
        $this->rules('json');

        return $this;
    }

    /**
     * Field must be alpha characters only.
     *
     * @return $this
     */
    public function alpha(): self
    {
        $this->rules('alpha');

        return $this;
    }

    /**
     * Field must be alpha-numeric characters only.
     *
     * @return $this
     */
    public function alphaNum(): self
    {
        $this->rules('alpha_num');

        return $this;
    }

    /**
     * Field must be alpha-numeric characters, dashes, and underscores.
     *
     * @return $this
     */
    public function alphaDash(): self
    {
        $this->rules('alpha_dash');

        return $this;
    }

    /**
     * Field value must be after a given date.
     *
     * @param  string  $date
     * @return $this
     */
    public function after($date): self
    {
        $this->rules("after:$date");

        return $this;
    }

    /**
     * Field value must be after or equal to a given date.
     *
     * @param  string  $date
     * @return $this
     */
    public function afterOrEqual($date): self
    {
        $this->rules("after_or_equal:$date");

        return $this;
    }

    /**
     * Field value must be before a given date.
     *
     * @param  string  $date
     * @return $this
     */
    public function before($date): self
    {
        $this->rules("before:$date");

        return $this;
    }

    /**
     * Field value must be before or equal to a given date.
     *
     * @param  string  $date
     * @return $this
     */
    public function beforeOrEqual($date): self
    {
        $this->rules("before_or_equal:$date");

        return $this;
    }

    /**
     * Field value must be different from another field.
     *
     * @param  string  $field
     * @return $this
     */
    public function different($field): self
    {
        $this->rules("different:$field");

        return $this;
    }

    /**
     * Field value must be the same as another field.
     *
     * @param  string  $field
     * @return $this
     */
    public function same($field): self
    {
        $this->rules("same:$field");

        return $this;
    }

    /**
     * Field must be filled if present.
     *
     * @return $this
     */
    public function filled(): self
    {
        $this->rules('filled');

        return $this;
    }

    /**
     * Field must be present in the request.
     *
     * @return $this
     */
    public function present(): self
    {
        $this->rules('present');

        return $this;
    }

    /**
     * Field must be an uploaded file.
     *
     * @return $this
     */
    public function isFile(): self
    {
        $this->rules('file');

        return $this;
    }

    /**
     * Field must be an image (jpeg, png, bmp, gif, svg, or webp).
     *
     * @return $this
     */
    public function isImage(): self
    {
        $this->rules('image');

        return $this;
    }

    /**
     * Field must be one of the given values.
     *
     * @return $this
     */
    public function in(array $values): self
    {
        $this->rules(Rule::in($values));

        return $this;
    }

    /**
     * Field must not be one of the given values.
     *
     * @return $this
     */
    public function notIn(array $values): self
    {
        $this->rules(Rule::notIn($values));

        return $this;
    }

    /**
     * Validate field with specific date format.
     *
     * @param  string  $format
     * @return $this
     */
    public function dateFormat($format): self
    {
        $this->rules("date_format:$format");

        return $this;
    }

    /**
     * Field is required if another field equals a value.
     *
     * @param  string  $field
     * @param  mixed  $value
     * @return $this
     */
    public function requiredIf($field, $value): self
    {
        $this->rules("required_if:$field,$value");

        return $this;
    }

    /**
     * Field is required unless another field equals a value.
     *
     * @param  string  $field
     * @param  mixed  $value
     * @return $this
     */
    public function requiredUnless($field, $value): self
    {
        $this->rules("required_unless:$field,$value");

        return $this;
    }

    /**
     * Field is required with any of the given fields.
     *
     * @param  array|string  $fields
     * @return $this
     */
    public function requiredWith($fields): self
    {
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $this->rules("required_with:$fields");

        return $this;
    }

    /**
     * Field is required with all of the given fields.
     *
     * @param  array|string  $fields
     * @return $this
     */
    public function requiredWithAll($fields): self
    {
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $this->rules("required_with_all:$fields");

        return $this;
    }

    /**
     * Field is required without any of the given fields.
     *
     * @param  array|string  $fields
     * @return $this
     */
    public function requiredWithout($fields): self
    {
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $this->rules("required_without:$fields");

        return $this;
    }

    /**
     * Field is required without all of the given fields.
     *
     * @param  array|string  $fields
     * @return $this
     */
    public function requiredWithoutAll($fields): self
    {
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $this->rules("required_without_all:$fields");

        return $this;
    }

    /**
     * Field must be a multiple of value.
     *
     * @param  int  $value
     * @return $this
     */
    public function multipleOf($value): self
    {
        $this->rules("multiple_of:$value");

        return $this;
    }

    /**
     * Field must be a valid timezone.
     *
     * @return $this
     */
    public function timezone(): self
    {
        $this->rules('timezone');

        return $this;
    }

    /**
     * Field must match the authenticated user's password.
     *
     * @return $this
     */
    public function currentPassword(): self
    {
        $this->rules('current_password');

        return $this;
    }

    /**
     * Field must be a valid MAC address.
     *
     * @return $this
     */
    public function macAddress(): self
    {
        $this->rules('mac_address');

        return $this;
    }

    /**
     * Field value must end with one of the given values.
     *
     * @param  array|string  $values
     * @return $this
     */
    public function endsWith($values): self
    {
        $values = is_array($values) ? implode(',', $values) : $values;
        $this->rules("ends_with:$values");

        return $this;
    }

    /**
     * Field value must start with one of the given values.
     *
     * @param  array|string  $values
     * @return $this
     */
    public function startsWith($values): self
    {
        $values = is_array($values) ? implode(',', $values) : $values;
        $this->rules("starts_with:$values");

        return $this;
    }
}
