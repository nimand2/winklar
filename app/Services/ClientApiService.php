<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Anlass;
use App\Models\Schussdaten;
use App\Models\Standblatt;

final class ClientApiService
{
    public function __construct(
        private readonly Anlass $anlassModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
    ) {
    }

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

    public function importShots(int $anlassId, array $shots, ?int $userId = null): int
    {
        $rows = [];

        foreach ($shots as $shot) {
            if (!is_array($shot)) {
                continue;
            }

            $rows[] = [
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
                'time_since_change' => $this->intValue($shot, 'TimeSinceChange'),
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
        }

        return $this->schussdatenModel->createMany($rows);
    }

    private function stringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? null;
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }

    private function intValue(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? $default;

        return (int) $value;
    }

    private function decimalValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? $data[lcfirst($key)] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace(',', '.', (string) $value);
    }

    private function dateTimeValue(array $data, string $key): ?string
    {
        $value = trim((string) ($data[$key] ?? $data[lcfirst($key)] ?? ''));
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
