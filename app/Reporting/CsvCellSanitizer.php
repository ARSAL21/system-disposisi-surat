<?php

namespace App\Reporting;

final class CsvCellSanitizer
{
    public function sanitize(string|int|float|null $value): string|int|float
    {
        if (! is_string($value)) {
            return $value ?? '';
        }

        return preg_match('/^[=+\-@]/u', ltrim($value)) === 1
            ? "'".$value
            : $value;
    }
}
