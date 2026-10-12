<?php
declare(strict_types=1);
namespace App\Services;

final class SchussStatus
{
    public static function istAktiv(array $schuss): bool
    {
        // SIUSData.pdf, p. 39: 1 = inserted, 2 = ignore. Local deletion is separate.
        return empty($schuss['manuell_geloescht']) && (int) ($schuss['ins_del'] ?? 0) !== 2;
    }
}
