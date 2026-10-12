<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Anlass;
use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Validation\FormInput;

final class ClientApiService
{
    public function __construct(
        private readonly Anlass $anlassModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
    ) {
    }

    /**
     * Liefert Anlaesse im kompakten Format fuer externe Clients.
     */
    public function listAnlaesse(): array
    {
        return array_map(
            static fn (array $anlass): array => [
                'id' => (int) $anlass['id'],
                'name' => (string) $anlass['name_anlass'],
            ],
            $this->anlassModel->getAll()
        );
    }

    /**
     * Liefert geloeste Standblaetter als Schuetzenliste fuer den Client.
     */
    public function listShooters(int $anlassId, int $sinceId = 0): array
    {
        return array_map(
            static fn (array $shooter): array => [
                'id' => (int) $shooter['id'],
                'startnummer' => (int) $shooter['startnummer'],
                'name' => (string) $shooter['name'],
                'vorname' => (string) ($shooter['vorname'] ?? ''),
                'verein' => (string) ($shooter['verein'] ?? ''),
                'bahn' => 0,
                'abloesung' => 0,
                'aktiv' => true,
            ],
            $this->standblattModel->findShootersForAnlass($anlassId, $sinceId)
        );
    }

    /**
     * Normalisiert importierte Schussdaten und speichert nur neue Datensaetze.
     */
    public function importShots(int $anlassId, array $shots, ?int $userId = null): int
    {
        $rows = [];
        if (!array_is_list($shots) || count($shots) > 500) throw new \InvalidArgumentException('Shots muss eine Liste mit höchstens 500 Datensätzen sein.');
        if ($this->anlassModel->findById($anlassId) === null) throw new \InvalidArgumentException('Anlass nicht gefunden.');

        foreach ($shots as $index => $shot) {
            if (!is_array($shot) || $shot === []) throw new \InvalidArgumentException('Ungültiger Schuss an Position ' . ($index + 1) . '.');
            $normalised = [];
            foreach ($shot as $key => $value) {
                $key = lcfirst((string) $key);
                if (array_key_exists($key, $normalised) && $normalised[$key] !== $value) {
                    throw new \InvalidArgumentException('Widersprüchliches Schussfeld: ' . $key);
                }
                $normalised[$key] = $value;
            }
            $shot = $normalised;
            $this->validateShot($shot, $index);

            $row = [
                'id_anlass' => $anlassId,
                'start_nr' => $this->stringValue($shot, 'StartNr'),
                'primaerwertung' => $this->decimalValue($shot, 'Primaerwertung'),
                'schussart' => $this->stringValue($shot, 'Schussart'),
                'bahn_nr' => $this->stringValue($shot, 'BahnNr'),
                'sekundaerwertung' => $this->decimalValue($shot, 'Sekundaerwertung'),
                'teiler' => $this->decimalValue($shot, 'Teiler'),
                'schuss_zeit' => $this->dateTimeValue($shot, 'Zeit'),
                'mouche' => $this->intValue($shot, 'Mouche'),
                'x_koordinate' => $this->decimalValue($shot, 'X'),
                'y_koordinate' => $this->decimalValue($shot, 'Y'),
                'in_time' => $this->intValue($shot, 'InTime', 1),
                'time_since_change' => $this->decimalValue($shot, 'TimeSinceChange'),
                'sweep_direction' => $this->stringValue($shot, 'SweepDirection'),
                'demonstration' => $this->intValue($shot, 'Demonstration'),
                'match_index' => $this->intValue($shot, 'Match'),
                'stich_index' => $this->intValue($shot, 'Stich'),
                'ins_del' => $this->intValue($shot, 'InsDel'),
                'total_art' => $this->stringValue($shot, 'TotalArt'),
                'gruppe' => $this->stringValue($shot, 'Gruppe'),
                'feuerart' => $this->stringValue($shot, 'Feuerart'),
                'log_event' => $this->stringValue($shot, 'LogEvent'),
                'log_typ' => $this->stringValue($shot, 'LogTyp'),
                'zeit_seit_jahresanfang' => $this->intValue($shot, 'ZeitSeitJahresbeginn'),
                'abloesung' => $this->stringValue($shot, 'Abloesung'),
                'waffe' => $this->stringValue($shot, 'Waffe'),
                'position' => $this->stringValue($shot, 'Position'),
                'target_id' => $this->stringValue($shot, 'TargetId'),
                'externe_nummer' => $this->stringValue($shot, 'ExterneNummer'),
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
            ];
            $row['import_hash'] = $this->importHash($row);

            $rows[] = $row;
        }

        $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['start_nr'], $rows)));
        $existing = $this->standblattModel->existingIdsForAnlass($anlassId, $ids);
        $unknown = array_diff($ids, $existing);
        if ($unknown !== []) throw new \InvalidArgumentException('Startnummer gehört nicht zu diesem Anlass: ' . implode(', ', $unknown));
        return $this->schussdatenModel->createMany($rows, true);
    }

    private function validateShot(array $shot, int $index): void
    {
        $source = [];
        foreach ($shot as $key => $value) $source[lcfirst((string) $key)] = $value;
        $input = new FormInput($source);
        $input->integer('startNr', 1, 4294967295, true);
        $input->text('externeNummer', 100, true);
        $input->decimal('primaerwertung', 0, 99999999.99, 2, true);
        foreach (['sekundaerwertung', 'teiler'] as $key) $input->decimal($key);
        foreach (['x', 'y'] as $key) $input->decimal($key, -999999.9999, 999999.9999, 4);
        $input->decimal('timeSinceChange', 0, 9999999999.9999, 4);
        foreach (['mouche', 'inTime', 'demonstration'] as $key) $input->integer($key, 0, 1);
        $input->integer('insDel', 0, 2);
        foreach (['match', 'stich', 'zeitSeitJahresbeginn'] as $key) $input->integer($key, 0, 4294967295);
        foreach (['schussart', 'bahnNr', 'sweepDirection', 'totalArt', 'gruppe', 'feuerart', 'logTyp', 'abloesung', 'waffe', 'position', 'targetId'] as $key) $input->integer($key, 0, 2147483647);
        $input->integer('logEvent', 0, PHP_INT_MAX);
        $time = $input->text('zeit', 40, true);
        if ($time !== null) FormInput::timestamp($time);
        if ($input->errors !== []) throw new \InvalidArgumentException('Schuss ' . ($index + 1) . ': ' . implode(' ', $input->errors));
    }

    /**
     * Liest einen Stringwert aus PascalCase- oder camelCase-Clientdaten.
     */
    private function stringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? null;
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }

    /**
     * Liest einen Integerwert aus Clientdaten.
     */
    private function intValue(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? $default;

        return (int) $value;
    }

    /**
     * Normalisiert Dezimalwerte aus Clientdaten auf Punktnotation.
     */
    private function decimalValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace(',', '.', (string) $value);
    }

    /**
     * Wandelt Client-Zeitstempel in das Datenbankformat um.
     */
    private function dateTimeValue(array $data, string $key): ?string
    {
        $value = trim((string) ($data[$key] ?? $data[lcfirst($key)] ?? ''));
        if ($value === '') {
            return null;
        }

        return FormInput::timestamp($value);
    }

    /**
     * Bildet einen stabilen Hash zur Dubletten-Erkennung beim Import.
     */
    private function importHash(array $row): string
    {
        $fields = [
            'id_anlass',
            'start_nr',
            'bahn_nr',
            'schuss_zeit',
            'match_index',
            'stich_index',
            'log_event',
            'log_typ',
            'zeit_seit_jahresanfang',
            'target_id',
            'primaerwertung',
            'sekundaerwertung',
            'teiler',
            'x_koordinate',
            'y_koordinate',
        ];

        $parts = [];
        foreach ($fields as $field) {
            $parts[] = $field . '=' . $this->hashValue($row, $field);
        }

        return hash('sha256', implode('|', $parts));
    }

    /**
     * Formatiert einzelne Felder konsistent fuer den Import-Hash.
     */
    private function hashValue(array $row, string $field): string
    {
        $value = $row[$field] ?? null;
        if ($value === null) {
            return '<null>';
        }

        return match ($field) {
            'primaerwertung', 'sekundaerwertung', 'teiler' => number_format((float) $value, 2, '.', ''),
            'x_koordinate', 'y_koordinate' => number_format((float) $value, 4, '.', ''),
            default => (string) $value,
        };
    }
}
