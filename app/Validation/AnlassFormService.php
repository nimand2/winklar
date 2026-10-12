<?php
declare(strict_types=1);
namespace App\Validation;

final class AnlassFormService
{
    public static function sanitizeAnlassData(array $source, int $userId, array $existing = []): array
    {
        $input = new FormInput($source);
        $data = [
            'fk_adress_id_creator' => $existing['fk_adress_id_creator'] ?? null,
            'fk_adress_id_modifier' => $existing['fk_adress_id_modifier'] ?? null,
            'name_anlass' => $input->text('name_anlass', 190, true) ?? '',
            'shortname_anlass' => $input->text('shortname_anlass', 100),
            'start_anlass' => $input->date('start_anlass'),
            'end_anlass' => $input->date('end_anlass'),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
        $data['_errors'] = $input->errors;
        return $data;
    }

    public static function validateAnlassData(array $data): array
    {
        $errors = $data['_errors'] ?? [];
        if ($data['name_anlass'] === '') $errors[] = 'Bitte gib einen Namen für den Anlass ein.';
        if ($data['start_anlass'] !== null && $data['end_anlass'] !== null && $data['end_anlass'] < $data['start_anlass']) {
            $errors[] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
        }
        return array_values(array_unique($errors));
    }

    public static function sanitizeStichData(array $source, int $anlassId, int $userId, array $existing = []): array
    {
        $input = new FormInput($source);
        $data = [
            'id_anlass' => $anlassId,
            'id_disziplin' => $input->integer('id_disziplin', 0, 4294967295),
            'name' => $input->text('name', 150, true) ?? '',
            'short_name' => $input->text('short_name', 50),
            'anzeige_id' => $input->text('anzeige_id', 50),
            'scheibe' => $input->text('scheibe', 50),
            'wertigkeit' => $input->decimal('wertigkeit'),
            'anzahl_schuss' => $input->integer('anzahl_schuss', 1, 1000, true),
            'anzahl_passen' => $input->integer('anzahl_passen', 0, 1000),
            'preis' => $input->decimal('preis'),
            'verbindung' => $input->text('verbindung', 100),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
        $data['_errors'] = $input->errors;
        return $data;
    }

    public static function validateStichData(array $data): array
    {
        $errors = $data['_errors'] ?? [];
        if ($data['name'] === '') $errors[] = 'Bitte gib einen Namen für den Stich ein.';
        if (($data['anzahl_schuss'] ?? 0) < 1) $errors[] = 'Mindestens ein Schuss pro Serie erforderlich.';
        return array_values(array_unique($errors));
    }

    public static function gabe(array $source, int $userId, array $existing = []): array
    {
        $input = new FormInput($source);
        $data = [
            'name' => $input->text('name', 150, true) ?? '',
            'punktwert' => $input->decimal('punktwert') ?? '0.00',
            'preis' => $input->decimal('preis') ?? '0.00',
            'anzahl' => $input->integer('anzahl', 0, 4294967295) ?? 0,
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
        $data['_errors'] = $input->errors;
        return $data;
    }

    public static function regel(array $source, int $userId): array
    {
        $input = new FormInput($source);
        $data = [
            'stich_id' => $input->integer('stich_id', 1, 4294967295, true),
            'gaben_id' => $input->integer('gaben_id', 1, 4294967295, true),
            'min_wert' => $input->decimal('min_wert'), 'max_wert' => $input->decimal('max_wert'),
            'min_alter' => $input->integer('min_alter', 0, 150), 'max_alter' => $input->integer('max_alter', 0, 150),
            'created_by_user_id' => $userId, 'updated_by_user_id' => $userId,
        ];
        foreach (['wert', 'alter'] as $field) {
            if ($data['min_' . $field] !== null && $data['max_' . $field] !== null && $data['min_' . $field] > $data['max_' . $field]) {
                $input->errors[] = 'Min. ' . $field . ' darf nicht über Max. ' . $field . ' liegen.';
            }
        }
        $data['_errors'] = $input->errors;
        return $data;
    }
}
