<?php

namespace Binaryk\LaravelRestify\MCP\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException as BrickMathException;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\Support\Arr;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationData;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait SchemaAttributes
{
    /**
     * Validate that an attribute was "accepted".
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAccepted(string $attribute, $schema, array $parameters)
    {
        return $this->rulesSchema[$attribute] ?? $schema->string();
    }

    /**
     * Validate that an attribute was "accepted" when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateAcceptedIf($attribute, $value, $parameters)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        $this->requireParameterCount(2, $parameters, 'accepted_if');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
        }

        return true;
    }

    /**
     * Validate that an attribute was "declined".
     *
     * This validation rule implies the attribute is "required".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateDeclined($attribute, $value)
    {
        $acceptable = ['no', 'off', '0', 0, false, 'false'];

        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute was "declined" when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateDeclinedIf($attribute, $value, $parameters)
    {
        $acceptable = ['no', 'off', '0', 0, false, 'false'];

        $this->requireParameterCount(2, $parameters, 'declined_if');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
        }

        return true;
    }

    /**
     * Validate that an attribute is an active URL.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateActiveUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            try {
                $records = $this->getDnsRecords($url.'.', DNS_A | DNS_AAAA);

                if (is_array($records) && count($records) > 0) {
                    return true;
                }
            } catch (Exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get the DNS records for the given hostname.
     *
     * @param  string  $hostname
     * @param  int  $type
     * @return array|false
     */
    protected function getDnsRecords($hostname, $type)
    {
        return dns_get_record($hostname, $type);
    }

    /**
     * Validate that an attribute is 7 bit ASCII.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAscii($attribute, $value)
    {
        return Str::isAscii($value);
    }

    /**
     * "Break" on first validation fail.
     *
     * Always returns true, just lets us put "bail" in rules.
     *
     * @return bool
     */
    public function validateBail()
    {
        return true;
    }

    /**
     * Validate the date is before a given date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateBefore(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("This is a date attribute. Must be before: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate the date is before or equal a given date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateBeforeOrEqual(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Must be before or equal to: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate the date is after a given date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAfter(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Date attribute, must be after: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate the date is equal or after a given date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAfterOrEqual(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Must be after or equal to: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Compare a given date against another using an operator.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @param  string  $operator
     * @return bool
     */
    protected function compareDates($attribute, $value, $parameters, $operator)
    {
        if (! is_string($value) && ! is_numeric($value) && ! $value instanceof DateTimeInterface) {
            return false;
        }

        if ($format = $this->getDateFormat($attribute)) {
            return $this->checkDateTimeOrder($format, $value, $parameters[0], $operator);
        }

        if (is_null($date = $this->getDateTimestamp($parameters[0]))) {
            $date = $this->getDateTimestamp($this->getValue($parameters[0]));
        }

        return $this->compare($this->getDateTimestamp($value), $date, $operator);
    }

    /**
     * Get the date format for an attribute if it has one.
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected function getDateFormat($attribute)
    {
        if ($result = $this->getRule($attribute, 'DateFormat')) {
            return $result[1][0];
        }
    }

    /**
     * Get the date timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function getDateTimestamp($value)
    {
        $date = is_null($value) ? null : $this->getDateTime($value);

        return $date ? $date->getTimestamp() : null;
    }

    /**
     * Given two date/time strings, check that one is after the other.
     *
     * @param  string  $format
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @return bool
     */
    protected function checkDateTimeOrder($format, $first, $second, $operator)
    {
        $firstDate = $this->getDateTimeWithOptionalFormat($format, $first);

        $format = $this->getDateFormat($second) ?: $format;

        if (! $secondDate = $this->getDateTimeWithOptionalFormat($format, $second)) {
            if (is_null($second = $this->getValue($second))) {
                return true;
            }

            $secondDate = $this->getDateTimeWithOptionalFormat($format, $second);
        }

        return ($firstDate && $secondDate) && $this->compare($firstDate, $secondDate, $operator);
    }

    /**
     * Get a DateTime instance from a string.
     *
     * @param  string  $format
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTimeWithOptionalFormat($format, $value)
    {
        if ($date = DateTime::createFromFormat('!'.$format, $value)) {
            return $date;
        }

        return $this->getDateTime($value);
    }

    /**
     * Get a DateTime instance from a string with no format.
     *
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTime($value)
    {
        try {
            return @Date::parse($value) ?: null;
        } catch (Exception) {
            //
        }
    }

    /**
     * Validate that an attribute contains only alphabetic characters.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAlpha(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must contain only alphabetic characters');
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, and underscores.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAlphaDash(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must contain only alpha-numeric characters, dashes, and underscores');
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateAlphaNum(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must contain only alpha-numeric characters');
    }

    /**
     * Validate that an attribute is an array.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateArray(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof ArrayType) {
            return $existing;
        }

        return $schema->array()->description('Must be an array');
    }

    /**
     * Validate that an attribute is a list.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateList($attribute, $value)
    {
        return is_array($value) && array_is_list($value);
    }

    /**
     * Validate that an array has all of the given keys.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateRequiredArrayKeys($attribute, $value, $parameters)
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($parameters as $param) {
            if (! Arr::exists($value, $param)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the size of an attribute is between a set of values.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateBetween(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        $description = match (true) {
            $type instanceof StringType => "Must be between {$parameters[0]} and {$parameters[1]} characters",
            $type instanceof ArrayType => "Must have between {$parameters[0]} and {$parameters[1]} items",
            default => "Must be between {$parameters[0]} and {$parameters[1]}",
        };

        return $type->min((int) $parameters[0])->max((int) $parameters[1])->description($description);
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateBoolean(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof \Illuminate\JsonSchema\Types\BooleanType) {
            return $existing;
        }

        return $schema->boolean()->description('Must be a boolean (true/false)');
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array{0: string}  $parameters
     * @return bool
     */
    public function validateConfirmed($attribute, $value, $parameters)
    {
        return $this->validateSame($attribute, $value, [$parameters[0] ?? $attribute.'_confirmation']);
    }

    /**
     * Validate an attribute contains a list of values.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateContains(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->array();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must contain: {$values}");
        }

        return $type;
    }

    /**
     * Validate an attribute does not contain a list of values.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDoesntContain(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->array();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must not contain: {$values}");
        }

        return $type;
    }

    /**
     * Validate that the password of the currently authenticated user matches the given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    protected function validateCurrentPassword($attribute, $value, $parameters)
    {
        $auth = $this->container->make('auth');
        $hasher = $this->container->make('hash');

        $guard = $auth->guard(Arr::first($parameters));

        if ($guard->guest()) {
            return false;
        }

        return $hasher->check($value, $guard->user()->getAuthPassword());
    }

    /**
     * Validate that an attribute is a valid date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDate(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid date format.');
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDateFormat(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $formats = implode(', ', $parameters);
            $description = count($parameters) > 1
                ? "Must match one of date formats: {$formats}"
                : "Must match date format: {$formats}";

            return $type->description($description);
        }

        return $type;
    }

    /**
     * Validate that an attribute is equal to another date.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDateEquals(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Must be equal to date: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate that an attribute has a given number of decimal places.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDecimal($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'decimal');

        if (! $this->validateNumeric($attribute, $value, [])) {
            return false;
        }

        $matches = [];

        if (preg_match('/^[+-]?\d*\.?(\d*)$/', $value, $matches) !== 1) {
            return false;
        }

        $decimals = strlen(end($matches));

        if (! isset($parameters[1])) {
            return $decimals == $parameters[0];
        }

        return $decimals >= $parameters[0] &&
            $decimals <= $parameters[1];
    }

    /**
     * Validate that an attribute is different from another attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDifferent($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'different');

        foreach ($parameters as $parameter) {
            if (Arr::has($this->data, $parameter)) {
                $other = Arr::get($this->data, $parameter);

                if ($value === $other) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate that an attribute has a given number of digits.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'digits');

        return ! preg_match('/[^0-9]/', $value)
            && strlen((string) $value) == $parameters[0];
    }

    /**
     * Validate that an attribute is between a given number of digits.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDigitsBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9]/', $value)
            && $length >= $parameters[0] && $length <= $parameters[1];
    }

    /**
     * Validate the dimensions of an image matches the given values.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDimensions($attribute, $value, $parameters)
    {
        if ($this->isValidFileInstance($value) && in_array($value->getMimeType(), ['image/svg+xml', 'image/svg'])) {
            return true;
        }

        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        $dimensions = method_exists($value, 'dimensions')
            ? $value->dimensions()
            : @getimagesize($value->getRealPath());

        if (! $dimensions) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'dimensions');

        [$width, $height] = $dimensions;

        $parameters = $this->parseNamedParameters($parameters);

        return ! (
            $this->failsBasicDimensionChecks($parameters, $width, $height) ||
            $this->failsRatioCheck($parameters, $width, $height) ||
            $this->failsMinRatioCheck($parameters, $width, $height) ||
            $this->failsMaxRatioCheck($parameters, $width, $height)
        );
    }

    /**
     * Test if the given width and height fail any conditions.
     *
     * @param  array<string,string>  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsBasicDimensionChecks($parameters, $width, $height)
    {
        return (isset($parameters['width']) && $parameters['width'] != $width) ||
            (isset($parameters['min_width']) && $parameters['min_width'] > $width) ||
            (isset($parameters['max_width']) && $parameters['max_width'] < $width) ||
            (isset($parameters['height']) && $parameters['height'] != $height) ||
            (isset($parameters['min_height']) && $parameters['min_height'] > $height) ||
            (isset($parameters['max_height']) && $parameters['max_height'] < $height);
    }

    /**
     * Determine if the given parameters fail a dimension ratio check.
     *
     * @param  array<string,string>  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsRatioCheck($parameters, $width, $height)
    {
        if (! isset($parameters['ratio'])) {
            return false;
        }

        [$numerator, $denominator] = array_replace(
            [1, 1], array_filter(sscanf($parameters['ratio'], '%f/%d'))
        );

        $precision = 1 / (max(($width + $height) / 2, $height) + 1);

        return abs($numerator / $denominator - $width / $height) > $precision;
    }

    /**
     * Determine if the given parameters fail a dimension minimum ratio check.
     *
     * @param  array<string,string>  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    private function failsMinRatioCheck($parameters, $width, $height)
    {
        if (! isset($parameters['min_ratio'])) {
            return false;
        }

        [$minNumerator, $minDenominator] = array_replace(
            [1, 1], array_filter(sscanf($parameters['min_ratio'], '%f/%d'))
        );

        return ($width / $height) > ($minNumerator / $minDenominator);
    }

    /**
     * Determine if the given parameters fail a dimension maximum ratio check.
     *
     * @param  array<string,string>  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    private function failsMaxRatioCheck($parameters, $width, $height)
    {
        if (! isset($parameters['max_ratio'])) {
            return false;
        }

        [$maxNumerator, $maxDenominator] = array_replace(
            [1, 1], array_filter(sscanf($parameters['max_ratio'], '%f/%d'))
        );

        return ($width / $height) < ($maxNumerator / $maxDenominator);
    }

    /**
     * Validate an attribute is unique among other values.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateDistinct($attribute, $value, $parameters)
    {
        $data = Arr::except($this->getDistinctValues($attribute), $attribute);

        if (in_array('ignore_case', $parameters)) {
            return empty(preg_grep('/^'.preg_quote($value, '/').'$/iu', $data));
        }

        return ! in_array($value, array_values($data), in_array('strict', $parameters));
    }

    /**
     * Get the values to distinct between.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getDistinctValues($attribute)
    {
        $attributeName = $this->getPrimaryAttribute($attribute);

        if (! property_exists($this, 'distinctValues')) {
            return $this->extractDistinctValues($attributeName);
        }

        if (! array_key_exists($attributeName, $this->distinctValues)) {
            $this->distinctValues[$attributeName] = $this->extractDistinctValues($attributeName);
        }

        return $this->distinctValues[$attributeName];
    }

    /**
     * Extract the distinct values from the data.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function extractDistinctValues($attribute)
    {
        $attributeData = ValidationData::extractDataFromPath(
            ValidationData::getLeadingExplicitAttributePath($attribute), $this->data
        );

        $pattern = str_replace('\*', '[^.]+', preg_quote($attribute, '#'));

        return Arr::where(Arr::dot($attributeData), function ($value, $key) use ($pattern) {
            return (bool) preg_match('#^'.$pattern.'\z#u', $key);
        });
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateEmail(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid email address.');
    }

    /**
     * Validate the existence of an attribute value in a database table.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateExists(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        return $type->description('Must exist in the database');
    }

    /**
     * Get the number of records that exist in storage.
     *
     * @param  mixed  $connection
     * @param  string  $table
     * @param  string  $column
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return int
     */
    protected function getExistCount($connection, $table, $column, $value, $parameters)
    {
        $verifier = $this->getPresenceVerifier($connection);

        $extra = $this->getExtraConditions(
            array_values(array_slice($parameters, 2))
        );

        if ($this->currentRule instanceof Exists) {
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }

        return is_array($value)
            ? $verifier->getMultiCount($table, $column, $value, $extra)
            : $verifier->getCount($table, $column, $value, null, null, $extra);
    }

    /**
     * Validate the uniqueness of an attribute value on a given database table.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateUnique(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        return $type->description('Must be unique in the database.');
    }

    /**
     * Get the excluded ID column and value for the unique rule.
     *
     * @param  string|null  $idColumn
     * @param  array<int, int|string>  $parameters
     * @return array
     */
    protected function getUniqueIds($idColumn, $parameters)
    {
        $idColumn ??= $parameters[3] ?? 'id';

        return [$idColumn, $this->prepareUniqueId($parameters[2])];
    }

    /**
     * Prepare the given ID for querying.
     *
     * @param  mixed  $id
     * @return int
     */
    protected function prepareUniqueId($id)
    {
        if (preg_match('/\[(.*)\]/', $id, $matches)) {
            $id = $this->getValue($matches[1]);
        }

        if (strtolower($id) === 'null') {
            $id = null;
        }

        if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
            $id = (int) $id;
        }

        return $id;
    }

    /**
     * Get the extra conditions for a unique rule.
     *
     * @param  array<int, int|string>  $parameters
     * @return array
     */
    protected function getUniqueExtra($parameters)
    {
        if (isset($parameters[4])) {
            return $this->getExtraConditions(array_slice($parameters, 4));
        }

        return [];
    }

    /**
     * Parse the connection / table for the unique / exists rules.
     *
     * @param  string  $table
     * @return array
     */
    public function parseTable($table)
    {
        [$connection, $table] = str_contains($table, '.') ? explode('.', $table, 2) : [null, $table];

        if (str_contains($table, '\\') && class_exists($table) && is_a($table, Model::class, true)) {
            $model = new $table;

            $table = $model->getTable();
            $connection ??= $model->getConnectionName();

            if (str_contains($table, '.') && Str::startsWith($table, $connection)) {
                $connection = null;
            }

            $idColumn = $model->getKeyName();
        }

        return [$connection, $table, $idColumn ?? null];
    }

    /**
     * Get the column name for an exists / unique query.
     *
     * @param  array<int, int|string>  $parameters
     * @param  string  $attribute
     * @return int|string
     */
    public function getQueryColumn($parameters, $attribute)
    {
        return isset($parameters[1]) && $parameters[1] !== 'NULL'
            ? $parameters[1]
            : $this->guessColumnForQuery($attribute);
    }

    /**
     * Guess the database column from the given attribute name.
     *
     * @param  string  $attribute
     * @return string
     */
    public function guessColumnForQuery($attribute)
    {
        if (in_array($attribute, Arr::collapse($this->implicitAttributes))
            && ! is_numeric($last = last(explode('.', $attribute)))) {
            return $last;
        }

        return $attribute;
    }

    /**
     * Get the extra conditions for a unique / exists rule.
     *
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    /**
     * Validate the extension of a file upload attribute is in a set of defined extensions.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateExtensions($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        return in_array(strtolower($value->getClientOriginalExtension()), $parameters);
    }

    /**
     * Validate the given value is a valid file.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateFile(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid file');
    }

    /**
     * Validate the given attribute is filled if it is present.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateFilled($attribute, $value)
    {
        if (Arr::has($this->data, $attribute)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is greater than another attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateGt($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'gt');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Gt');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            try {
                return BigNumber::of($this->getSize($attribute, $value))->isGreaterThan($this->trim($parameters[0]));
            } catch (MathException) {
                return false;
            }
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            try {
                return BigNumber::of($this->trim($value))->isGreaterThan($this->trim($comparedToValue));
            } catch (MathException) {
                return false;
            }
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        try {
            return $this->getSize($attribute, $value) > $this->getSize($attribute, $comparedToValue);
        } catch (MathException) {
            return false;
        }
    }

    /**
     * Validate that an attribute is less than another attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateLt($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'lt');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Lt');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            try {
                return BigNumber::of($this->getSize($attribute, $value))->isLessThan($this->trim($parameters[0]));
            } catch (MathException) {
                return false;
            }
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return BigNumber::of($this->trim($value))->isLessThan($this->trim($comparedToValue));
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        try {
            return $this->getSize($attribute, $value) < $this->getSize($attribute, $comparedToValue);
        } catch (MathException) {
            return false;
        }
    }

    /**
     * Validate that an attribute is greater than or equal another attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateGte($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'gte');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Gte');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            try {
                return BigNumber::of($this->getSize($attribute,
                    $value))->isGreaterThanOrEqualTo($this->trim($parameters[0]));
            } catch (MathException) {
                return false;
            }
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            try {
                return BigNumber::of($this->trim($value))->isGreaterThanOrEqualTo($this->trim($comparedToValue));
            } catch (MathException) {
                return false;
            }
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        try {
            return $this->getSize($attribute, $value) >= $this->getSize($attribute, $comparedToValue);
        } catch (MathException) {
            return false;
        }
    }

    /**
     * Validate that an attribute is less than or equal another attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateLte($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'lte');

        $comparedToValue = $this->getValue($parameters[0]);

        $this->shouldBeNumeric($attribute, 'Lte');

        if (is_null($comparedToValue) && (is_numeric($value) && is_numeric($parameters[0]))) {
            try {
                return BigNumber::of($this->getSize($attribute,
                    $value))->isLessThanOrEqualTo($this->trim($parameters[0]));
            } catch (MathException) {
                return false;
            }
        }

        if (is_numeric($parameters[0])) {
            return false;
        }

        if ($this->hasRule($attribute, $this->numericRules) && is_numeric($value) && is_numeric($comparedToValue)) {
            return BigNumber::of($this->trim($value))->isLessThanOrEqualTo($this->trim($comparedToValue));
        }

        if (! $this->isSameType($value, $comparedToValue)) {
            return false;
        }

        try {
            return $this->getSize($attribute, $value) <= $this->getSize($attribute, $comparedToValue);
        } catch (MathException) {
            return false;
        }
    }

    /**
     * Validate that an attribute is lowercase.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateLowercase($attribute, $value, $parameters)
    {
        return Str::lower($value) === $value;
    }

    /**
     * Validate that an attribute is uppercase.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateUppercase($attribute, $value, $parameters)
    {
        return Str::upper($value) === $value;
    }

    /**
     * Validate that an attribute is a valid HEX color.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateHexColor($attribute, $value)
    {
        return preg_match('/^#(?:(?:[0-9a-f]{3}){1,2}|(?:[0-9a-f]{4}){1,2})$/i', $value) === 1;
    }

    /**
     * Validate the MIME type of a file is an image MIME type.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateImage(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid image file');
    }

    /**
     * Validate an attribute is contained within a list of values.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateIn(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->enum($parameters)->description("Must be one of: {$values}");
        }

        return $type->enum($parameters);
    }

    /**
     * Validate that the values of an attribute are in another attribute.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateInArray(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Must be a value from {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate that an array has at least one of the given keys.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateInArrayKeys($attribute, $value, $parameters)
    {
        if (! is_array($value)) {
            return false;
        }

        if (empty($parameters)) {
            return false;
        }

        foreach ($parameters as $param) {
            if (Arr::exists($value, $param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate that an attribute is an integer.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateInteger(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof \Illuminate\JsonSchema\Types\IntegerType) {
            return $existing;
        }

        return $schema->integer()->description('Must be an integer');
    }

    /**
     * Validate that an attribute is a valid IP.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateIp(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid IP address');
    }

    /**
     * Validate that an attribute is a valid IPv4.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateIpv4(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid IPv4 address');
    }

    /**
     * Validate that an attribute is a valid IPv6.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateIpv6(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid IPv6 address');
    }

    /**
     * Validate that an attribute is a valid MAC address.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateMacAddress(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid MAC address');
    }

    /**
     * Validate the attribute is a valid JSON string.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateJson(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be valid JSON');
    }

    /**
     * Validate the size of an attribute is less than or equal to a maximum value.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateMax(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $description = match (true) {
                $type instanceof StringType => "Maximum length: {$parameters[0]} characters",
                $type instanceof ArrayType => "Maximum items: {$parameters[0]}",
                default => "Maximum value: {$parameters[0]}",
            };

            return $type->max((int) $parameters[0])->description($description);
        }

        return $type->max((int) $parameters[0]);
    }

    /**
     * Validate that an attribute has a maximum number of digits.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMaxDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max_digits');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9]/', $value) && $length <= $parameters[0];
    }

    /**
     * Validate the guessed extension of a file upload is in a set of file extensions.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateMimes(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        if (! empty($parameters)) {
            $extensions = implode(', ', $parameters);

            return $schema->string()->description("Allowed file extensions: {$extensions}");
        }

        return $schema->string()->description('Must be a valid file with allowed extension');
    }

    /**
     * Validate the MIME type of a file upload attribute is in a set of MIME types.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateMimetypes(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        if (! empty($parameters)) {
            $types = implode(', ', $parameters);

            return $schema->string()->description("Allowed MIME types: {$types}");
        }

        return $schema->string()->description('Must be a valid file with allowed MIME type');
    }

    /**
     * Check if PHP uploads are explicitly allowed.
     *
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    protected function shouldBlockPhpUpload($value, $parameters)
    {
        if (in_array('php', $parameters)) {
            return false;
        }

        $phpExtensions = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        ];

        return ($value instanceof UploadedFile)
            ? in_array(trim(strtolower($value->getClientOriginalExtension())), $phpExtensions)
            : in_array(trim(strtolower($value->getExtension())), $phpExtensions);
    }

    /**
     * Validate the size of an attribute is greater than or equal to a minimum value.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateMin(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $description = match (true) {
                $type instanceof StringType => "Minimum length: {$parameters[0]} characters",
                $type instanceof ArrayType => "Minimum items: {$parameters[0]}",
                default => "Minimum value: {$parameters[0]}",
            };

            return $type->min((int) $parameters[0])->description($description);
        }

        return $type->min((int) $parameters[0]);
    }

    /**
     * Validate that an attribute has a minimum number of digits.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMinDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min_digits');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9]/', $value) && $length >= $parameters[0];
    }

    /**
     * Validate that an attribute is missing.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMissing($attribute, $value, $parameters)
    {
        return ! Arr::has($this->data, $attribute);
    }

    /**
     * Validate that an attribute is missing when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMissingIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'missing_if');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateMissing($attribute, $value, $parameters);
        }

        return true;
    }

    /**
     * Validate that an attribute is missing unless another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMissingUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'missing_unless');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (! in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateMissing($attribute, $value, $parameters);
        }

        return true;
    }

    /**
     * Validate that an attribute is missing when any given attribute is present.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMissingWith($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'missing_with');

        if (Arr::hasAny($this->data, $parameters)) {
            return $this->validateMissing($attribute, $value, $parameters);
        }

        return true;
    }

    /**
     * Validate that an attribute is missing when all given attributes are present.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMissingWithAll($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'missing_with_all');

        if (Arr::has($this->data, $parameters)) {
            return $this->validateMissing($attribute, $value, $parameters);
        }

        return true;
    }

    /**
     * Validate the value of an attribute is a multiple of a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateMultipleOf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'multiple_of');

        if (! $this->validateNumeric($attribute, $value, []) || ! $this->validateNumeric($attribute, $parameters[0],
            [])) {
            return false;
        }

        try {
            $numerator = BigDecimal::of($this->trim($value));
            $denominator = BigDecimal::of($this->trim($parameters[0]));

            if ($numerator->isZero() && $denominator->isZero()) {
                return false;
            }

            if ($numerator->isZero()) {
                return true;
            }

            if ($denominator->isZero()) {
                return false;
            }

            return $numerator->remainder($denominator)->isZero();
        } catch (BrickMathException $e) {
            throw new MathException('An error occurred while handling the multiple_of input values.', previous: $e);
        }
    }

    /**
     * "Indicate" validation should pass if value is null.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateNullable(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        return $type->description('This field is optional');
    }

    /**
     * Validate an attribute is not contained within a list of values.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateNotIn($attribute, $value, $parameters)
    {
        return ! $this->validateIn($attribute, $value, $parameters);
    }

    /**
     * Validate that an attribute is numeric.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateNumeric(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof \Illuminate\JsonSchema\Types\NumberType) {
            return $existing;
        }

        $newType = $schema->number()->description('Must be a numeric value');

        if ($existing && property_exists($existing, 'isRequired') && $existing->isRequired) {
            $newType->required();
        }

        return $newType;
    }

    /**
     * Validate that an attribute exists even if not filled.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validatePresent($attribute, $value)
    {
        return Arr::has($this->data, $attribute);
    }

    /**
     * Validate that an attribute is present when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validatePresentIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'present_if');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validatePresent($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is present unless another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validatePresentUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'present_unless');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (! in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validatePresent($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is present when any given attribute is present.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validatePresentWith($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'present_with');

        if (Arr::hasAny($this->data, $parameters)) {
            return $this->validatePresent($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is present when all given attributes are present.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validatePresentWithAll($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'present_with_all');

        if (Arr::has($this->data, $parameters)) {
            return $this->validatePresent($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute passes a regular expression check.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value) > 0;
    }

    /**
     * Validate that an attribute does not pass a regular expression check.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array<int, int|string>  $parameters
     * @return bool
     */
    public function validateNotRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'not_regex');

        return preg_match($parameters[0], $value) < 1;
    }

    /**
     * Validate that a required attribute exists.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateRequired(string $attribute, $schema, array $parameters)
    {
        $this->markAttributeAsRequired($attribute);

        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        return $type->required()->description('This field is required');
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateRequiredIf(string $attribute, $schema, array $parameters)
    {
        return $this->rulesSchema[$attribute] ?? $schema->string();
    }

    /**
     * Validate that an attribute exists when another attribute was "accepted".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredIfAccepted($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'required_if_accepted');

        if ($this->validateAccepted($parameters[0], $this->getValue($parameters[0]))) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute was "declined".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredIfDeclined($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'required_if_declined');

        if ($this->validateDeclined($parameters[0], $this->getValue($parameters[0]))) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute does not exist or is an empty string.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateProhibited($attribute, $value)
    {
        return ! $this->validateRequired($attribute, $value);
    }

    /**
     * Validate that an attribute does not exist when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibitedIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'prohibited_if');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (in_array($other, $values, is_bool($other) || is_null($other))) {
            return ! $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute does not exist when another attribute was "accepted".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibitedIfAccepted($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'prohibited_if_accepted');

        if ($this->validateAccepted($parameters[0], $this->getValue($parameters[0]))) {
            return $this->validateProhibited($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute does not exist when another attribute was "declined".
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibitedIfDeclined($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'prohibited_if_declined');

        if ($this->validateDeclined($parameters[0], $this->getValue($parameters[0]))) {
            return $this->validateProhibited($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute does not exist unless another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibitedUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'prohibited_unless');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (! in_array($other, $values, is_bool($other) || is_null($other))) {
            return ! $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that other attributes do not exist when this attribute exists.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibits($attribute, $value, $parameters)
    {
        if ($this->validateRequired($attribute, $value)) {
            foreach ($parameters as $parameter) {
                if ($this->validateRequired($parameter, Arr::get($this->data, $parameter))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Indicate that an attribute is excluded.
     *
     * @return bool
     */
    public function validateExclude()
    {
        return false;
    }

    /**
     * Indicate that an attribute should be excluded when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'exclude_if');

        if (! Arr::has($this->data, $parameters[0])) {
            return true;
        }

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        return ! in_array($other, $values, is_bool($other) || is_null($other));
    }

    /**
     * Indicate that an attribute should be excluded when another attribute does not have a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'exclude_unless');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        return in_array($other, $values, is_bool($other) || is_null($other));
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless');

        [$values, $other] = $this->parseDependentRuleParameters($parameters);

        if (! in_array($other, $values, is_bool($other) || is_null($other))) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Indicate that an attribute should be excluded when another attribute presents.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeWith($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'exclude_with');

        if (! Arr::has($this->data, $parameters[0])) {
            return true;
        }

        return false;
    }

    /**
     * Indicate that an attribute should be excluded when another attribute is missing.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateExcludeWithout($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'exclude_without');

        if ($this->anyFailingRequired($parameters)) {
            return false;
        }

        return true;
    }

    /**
     * Prepare the values and the other value for validation.
     *
     * @param  array<int, int|string>  $parameters
     * @return array
     */
    public function parseDependentRuleParameters($parameters)
    {
        $other = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if ($this->shouldConvertToBoolean($parameters[0]) || is_bool($other)) {
            $values = $this->convertValuesToBoolean($values);
        }

        if (is_null($other)) {
            $values = $this->convertValuesToNull($values);
        }

        return [$values, $other];
    }

    /**
     * Check if parameter should be converted to boolean.
     *
     * @param  string  $parameter
     * @return bool
     */
    protected function shouldConvertToBoolean($parameter)
    {
        return in_array('boolean', $this->rules[$parameter] ?? []);
    }

    /**
     * Convert the given values to boolean if they are string "true" / "false".
     *
     * @param  array  $values
     * @return array
     */
    protected function convertValuesToBoolean($values)
    {
        return array_map(function ($value) {
            if ($value === 'true') {
                return true;
            } elseif ($value === 'false') {
                return false;
            }

            return $value;
        }, $values);
    }

    /**
     * Convert the given values to null if they are string "null".
     *
     * @param  array  $values
     * @return array
     */
    protected function convertValuesToNull($values)
    {
        return array_map(function ($value) {
            return Str::lower($value) === 'null' ? null : $value;
        }, $values);
    }

    /**
     * Validate that an attribute exists when any other attribute exists.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWith($attribute, $value, $parameters)
    {
        if (! $this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes exist.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithAll($attribute, $value, $parameters)
    {
        if (! $this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithout($attribute, $value, $parameters)
    {
        if ($this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes do not.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateRequiredWithoutAll($attribute, $value, $parameters)
    {
        if ($this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Determine if any of the given attributes fail the required test.
     *
     * @return bool
     */
    protected function anyFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if (! $this->validateRequired($key, $this->getValue($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if all of the given attributes fail the required test.
     *
     * @return bool
     */
    protected function allFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if ($this->validateRequired($key, $this->getValue($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that two attributes match.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateSame(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            return $type->description("Must match: {$parameters[0]}");
        }

        return $type;
    }

    /**
     * Validate the size of an attribute.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateSize(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        $description = match (true) {
            $type instanceof StringType => "Must be exactly {$parameters[0]} characters",
            $type instanceof ArrayType => "Must contain exactly {$parameters[0]} items",
            default => "Must be exactly {$parameters[0]}",
        };

        return $type->min((int) $parameters[0])->max((int) $parameters[0])->description($description);
    }

    /**
     * "Validate" optional attributes.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateSometimes(string $attribute, $schema, array $parameters)
    {
        return $this->rulesSchema[$attribute] ?? $schema->string();
    }

    /**
     * Validate the attribute starts with a given substring (schema version).
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateStartsWith(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must start with: {$values}");
        }

        return $type;
    }

    /**
     * Validate the attribute does not start with a given substring (schema version).
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDoesntStartWith(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must not start with: {$values}");
        }

        return $type;
    }

    /**
     * Validate the attribute ends with a given substring (schema version).
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateEndsWith(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must end with: {$values}");
        }

        return $type;
    }

    /**
     * Validate the attribute does not end with a given substring (schema version).
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateDoesntEndWith(string $attribute, $schema, array $parameters)
    {
        $type = $this->rulesSchema[$attribute] ?? $schema->string();

        if (! empty($parameters)) {
            $values = implode(', ', $parameters);

            return $type->description("Must not end with: {$values}");
        }

        return $type;
    }

    /**
     * Validate that an attribute is a string.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateString(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        $newType = $schema->string()->description('Must be a string');

        if ($existing && property_exists($existing, 'isRequired') && $existing->isRequired) {
            $newType->required();
        }

        return $newType;
    }

    /**
     * Validate that an attribute is a valid timezone.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateTimezone(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid timezone');
    }

    /**
     * Validate that an attribute is a valid URL.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateUrl(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid URL');
    }

    /**
     * Validate that an attribute is a valid ULID.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateUlid(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid ULID');
    }

    /**
     * Validate that an attribute is a valid UUID.
     *
     * @param  \Illuminate\JsonSchema\JsonSchema  $schema
     * @return \Illuminate\JsonSchema\Types\Type
     */
    public function validateUuid(string $attribute, $schema, array $parameters)
    {
        $existing = $this->rulesSchema[$attribute] ?? null;

        if ($existing instanceof StringType) {
            return $existing;
        }

        return $schema->string()->description('Must be a valid UUID');
    }

    /**
     * Get the size of an attribute.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return int|float|string
     */
    protected function getSize($attribute, $value)
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        if (is_numeric($value) && $hasNumeric) {
            return $this->ensureExponentWithinAllowedRange($attribute, $this->trim($value));
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value ?? '');
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValidFileInstance($value)
    {
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $value instanceof File;
    }

    /**
     * Determine if a comparison passes between the given values.
     *
     * @param  mixed  $first
     * @param  mixed  $second
     * @param  string  $operator
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function compare($first, $second, $operator)
    {
        return match ($operator) {
            '<' => $first < $second,
            '>' => $first > $second,
            '<=' => $first <= $second,
            '>=' => $first >= $second,
            '=' => $first == $second,
            default => throw new InvalidArgumentException,
        };
    }

    /**
     * Parse named parameters to $key => $value items.
     *
     * @param  array<int, int|string>  $parameters
     * @return array
     */
    public function parseNamedParameters($parameters)
    {
        return array_reduce($parameters, function ($result, $item) {
            [$key, $value] = array_pad(explode('=', $item, 2), 2, null);

            $result[$key] = $value;

            return $result;
        });
    }

    /**
     * Require a certain number of parameters to be present.
     *
     * @param  int  $count
     * @param  array<int, int|string>  $parameters
     * @param  string  $rule
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * Check if the parameters are of the same type.
     *
     * @param  mixed  $first
     * @param  mixed  $second
     * @return bool
     */
    protected function isSameType($first, $second)
    {
        return gettype($first) == gettype($second);
    }

    /**
     * Adds the existing rule to the numericRules array if the attribute's value is numeric.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function shouldBeNumeric($attribute, $rule)
    {
        if (is_numeric($this->getValue($attribute))) {
            $this->numericRules[] = $rule;
        }
    }

    /**
     * Trim the value if it is a string.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function trim($value)
    {
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Ensure the exponent is within the allowed range.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return mixed
     *
     * @throws \Illuminate\Support\Exceptions\MathException
     */
    protected function ensureExponentWithinAllowedRange($attribute, $value)
    {
        $stringValue = (string) $value;

        if (! is_numeric($value) || ! Str::contains($stringValue, 'e', ignoreCase: true)) {
            return $value;
        }

        $scale = (int) (Str::contains($stringValue, 'e')
            ? Str::after($stringValue, 'e')
            : Str::after($stringValue, 'E'));

        $withinRange = (
            $this->ensureExponentWithinAllowedRangeUsing ?? fn ($scale) => $scale <= 1000 && $scale >= -1000
        )($scale, $attribute, $value);

        if (! $withinRange) {
            throw new MathException('Scientific notation exponent outside of allowed range.');
        }

        return $value;
    }
}
