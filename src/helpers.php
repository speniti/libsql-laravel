<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

if (! function_exists('is_blob')) {
    function is_blob(mixed $value): bool
    {
        return is_string($value) && (! ctype_print($value) || ! mb_check_encoding($value, 'UTF-8'));
    }
}

if (! function_exists('is_datetime')) {
    /** @param list<string>|string $formats */
    function is_datetime(string $value, array|string $formats = []): bool
    {
        /** @var list<string> $formats */
        $formats = ['Y-m-d H:i:s', 'Y-m-d', ...Arr::wrap($formats)];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            $alerts = DateTimeImmutable::getLastErrors() ?: [];

            $warnings = data_get($alerts, 'warning_count', 0);
            $errors = data_get($alerts, 'error_count', 0);

            if ($date && $warnings === 0 && $errors === 0) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('is_vector')) {
    function is_vector(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $element) {
            if (! is_numeric($element)) {
                return false;
            }
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
