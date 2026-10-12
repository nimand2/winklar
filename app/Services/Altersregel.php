<?php
declare(strict_types=1);
namespace App\Services;

final class Altersregel
{
    /** Age in the event year, consistently used for rankings and award limits. */
    public static function alter(mixed $geburtsdatum, mixed $anlassDatum): ?int
    {
        foreach ([$geburtsdatum, $anlassDatum] as $value) {
            if (!is_string($value)) return null;
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($date === false || $date->format('Y-m-d') !== $value) return null;
        }
        $age = (int) substr($anlassDatum, 0, 4) - (int) substr($geburtsdatum, 0, 4);
        return $age >= 0 && $age <= 150 ? $age : null;
    }
}
