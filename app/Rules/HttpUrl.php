<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value is a well-formed http/https URL.
 *
 * Laravel's built-in `url` rule accepts any scheme (including javascript:,
 * data:, etc.), which is too permissive for article/image links sourced
 * from a third-party API.
 */
class HttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        $host = parse_url($value, PHP_URL_HOST);

        if (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) $scheme), ['http', 'https'], true) || blank($host)) {
            $fail('The :attribute must be a valid http or https URL.');
        }
    }
}
