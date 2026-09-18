<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Rejects dates further in the future than a small clock-skew tolerance.
 *
 * News APIs occasionally report a `publishedAt` a few minutes ahead of our
 * clock, but an article dated days or weeks in the future signals bad data.
 */
class NotFutureDate implements ValidationRule
{
    public function __construct(protected int $toleranceMinutes = 5)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $date = Carbon::parse($value);
        } catch (Throwable) {
            $fail('The :attribute is not a valid date.');

            return;
        }

        if ($date->isAfter(Carbon::now()->addMinutes($this->toleranceMinutes))) {
            $fail('The :attribute cannot be in the future.');
        }
    }
}
