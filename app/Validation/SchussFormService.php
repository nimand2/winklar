<?php

declare(strict_types=1);

namespace App\Validation;

final class SchussFormService
{
    public static function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    public static function nullableDecimal(mixed $value): ?string
    {
        $value = trim(str_replace(',', '.', (string) ($value ?? '')));

        return is_numeric($value) ? $value : null;
    }

    public static function datetimeValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
