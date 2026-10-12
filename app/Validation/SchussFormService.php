<?php

declare(strict_types=1);

namespace App\Validation;

final class SchussFormService
{
    public static function nullableString(mixed $value): ?string
    {
        if ($value !== null && !is_string($value) && !is_int($value)) return null;
        $value = trim((string) ($value ?? ''));

        return $value === '' || mb_strlen($value, 'UTF-8') > 100 ? null : $value;
    }

    public static function nullableDecimal(mixed $value): ?string
    {
        $input = new FormInput(['value' => $value]);
        $result = $input->decimal('value');
        if ($input->errors !== []) throw new \InvalidArgumentException(implode(' ', $input->errors));
        return $result;
    }

    public static function datetimeValue(mixed $value): ?string
    {
        return FormInput::timestamp($value);
    }
}
