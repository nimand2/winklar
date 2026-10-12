<?php
declare(strict_types=1);
namespace App\Core;

final class JsonRequest
{
    public static function decode(string $raw): array
    {
        if (strlen($raw) > 10 * 1024 * 1024) throw new \InvalidArgumentException('JSON-Anfrage ist zu groß.');
        try {
            $object = json_decode($raw, false, 64, JSON_THROW_ON_ERROR);
            if (!$object instanceof \stdClass) throw new \InvalidArgumentException('Ein JSON-Objekt ist erforderlich.');
            return json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Ungültige JSON-Anfrage.', 0, $exception);
        }
    }
}
