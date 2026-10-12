<?php

declare(strict_types=1);

namespace App\Validation;

final class AnlassFormService
{
    public static function sanitizeAnlassData(array $source, int $userId, array $existing = []): array
    {
        return [
            'fk_adress_id_creator' => $existing['fk_adress_id_creator'] ?? null,
            'fk_adress_id_modifier' => $existing['fk_adress_id_modifier'] ?? null,
            'name_anlass' => trim((string) ($source['name_anlass'] ?? '')),
            'shortname_anlass' => self::nullableString($source['shortname_anlass'] ?? null),
            'start_anlass' => self::nullableString($source['start_anlass'] ?? null),
            'end_anlass' => self::nullableString($source['end_anlass'] ?? null),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
    }

    /**
     * Validiert Pflichtfelder und Datumslogik eines Anlasses.
     */
    public static function validateAnlassData(array $data): array
    {
        $errors = [];

        if ($data['name_anlass'] === '') {
            $errors[] = 'Bitte gib einen Namen für den Anlass ein.';
        }

        if (
            $data['start_anlass'] !== null
            && $data['end_anlass'] !== null
            && $data['end_anlass'] < $data['start_anlass']
        ) {
            $errors[] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
        }

        return $errors;
    }

    /**
     * Normalisiert Stichdaten aus Formularwerten.
     */
    public static function sanitizeStichData(array $source, int $anlassId, int $userId, array $existing = []): array
    {
        return [
            'id_anlass' => $anlassId,
            'id_disziplin' => self::nullableInt($source['id_disziplin'] ?? null),
            'name' => trim((string) ($source['name'] ?? '')),
            'short_name' => self::nullableString($source['short_name'] ?? null),
            'anzeige_id' => self::nullableString($source['anzeige_id'] ?? null),
            'scheibe' => self::nullableString($source['scheibe'] ?? null),
            'wertigkeit' => self::nullableDecimal($source['wertigkeit'] ?? null),
            'anzahl_schuss' => self::nullableInt($source['anzahl_schuss'] ?? null),
            'anzahl_passen' => self::nullableInt($source['anzahl_passen'] ?? null),
            'preis' => self::nullableDecimal($source['preis'] ?? null),
            'verbindung' => self::nullableString($source['verbindung'] ?? null),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
    }

    /**
     * Validiert Pflichtfelder eines Stiches.
     */
    public static function validateStichData(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Bitte gib einen Namen für den Stich ein.';
        }

        return $errors;
    }

    /**
     * Wandelt leere Formularwerte in null um.
     */
    public static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Wandelt optionale Dezimalwerte aus Formularen um.
     */
    public static function nullableDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (float) str_replace(',', '.', $value);
    }

    /**
     * Wandelt optionale Ganzzahlen aus Formularen um.
     */
    public static function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : max(0, (int) $value);
    }
}
