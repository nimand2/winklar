<?php
declare(strict_types=1);
namespace App\Services;

/** Shared assignment of paid series for billing and ranking. */
final class SerienService
{
    public static function build(array $stiche, array $schuesse): array
    {
        usort($schuesse, [Schussreihenfolge::class, 'compare']);

        $schuesseByExterneNummer = [];
        foreach ($schuesse as $schuss) {
            if (!SchussStatus::istAktiv($schuss)) {
                continue;
            }

            $externeNummer = self::externalNumber($schuss['externe_nummer'] ?? null);
            $schuesseByExterneNummer[$externeNummer][] = $schuss;
        }

        $rows = [];
        $usedExterneNummern = [];
        $usedSchussCountsByExterneNummer = [];
        $total = 0.0;

        foreach ($stiche as $stich) {
            $externeNummer = self::externalNumber($stich['anzeige_id'] ?? null);
            if ($externeNummer !== '') {
                $usedExterneNummern[] = $externeNummer;
            }

            $anzahlStiche = max(0, (int) ($stich['anzahl_stiche'] ?? 0));
            $anzahlSchuss = max(1, (int) ($stich['anzahl_schuss'] ?? 0));
            $stichSchuesse = $externeNummer === '' ? [] : ($schuesseByExterneNummer[$externeNummer] ?? []);

            for ($stichNummer = 0; $stichNummer < $anzahlStiche; $stichNummer++) {
                $rowStich = $stich;
                $rowStich['anzahl_stiche'] = 1;
                $rowStich['serie_nummer'] = $stichNummer + 1;
                $rowSchuesse = array_slice($stichSchuesse, $stichNummer * $anzahlSchuss, $anzahlSchuss);
                $row = self::buildStichRow($rowStich, $rowSchuesse);
                $rows[] = $row;
                $total += $row['total'];
            }

            if ($externeNummer !== '') {
                $usedSchussCountsByExterneNummer[$externeNummer] = $anzahlStiche * $anzahlSchuss;
            }
        }

        foreach ($schuesseByExterneNummer as $externeNummer => $stichSchuesse) {
            if (in_array((string) $externeNummer, $usedExterneNummern, true)) {
                $stichSchuesse = array_slice($stichSchuesse, $usedSchussCountsByExterneNummer[(string) $externeNummer] ?? 0);
            }

            if ($stichSchuesse === []) {
                continue;
            }

            $row = self::buildStichRow([
                'name' => $externeNummer === ''
                    ? 'Nicht zugeordneter Stich ohne externe Nummer'
                    : 'Nicht zugeordneter Stich ' . (string) $externeNummer,
                'short_name' => '',
                'anzahl_schuss' => count($stichSchuesse),
                'anzahl_stiche' => 1,
            ], $stichSchuesse);
            $rows[] = $row;
            $total += $row['total'];
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'schussCount' => array_sum(array_map(static fn (array $row): int => count($row['schuesse']), $rows)),
        ];
    }

    /**
     * Erstellt eine auswertbare Zeile fuer einen Stich mit seinen Schuessen.
     */
    private static function buildStichRow(array $stich, array $schuesse): array
    {
        $werte = [];
        $total = 0.0;

        foreach ($schuesse as $schuss) {
            $wert = self::numericValue($schuss['primaerwertung'] ?? null);
            $werte[] = [
                'id' => (int) ($schuss['id'] ?? 0),
                'primaerwertung' => $wert,
                'sekundaerwertung' => $schuss['sekundaerwertung'] ?? null,
                'mouche' => (int) ($schuss['mouche'] ?? 0) === 1,
                'zeit' => $schuss['schuss_zeit'] ?? null,
                'externe_nummer' => self::externalNumber($schuss['externe_nummer'] ?? null),
            ];
            $total += $wert;
        }

        $anzahlSchuss = (int) ($stich['anzahl_schuss'] ?? 0);
        $anzahlSchuss = $anzahlSchuss > 0 ? $anzahlSchuss : count($werte);

        return [
            'stich_id' => (int) ($stich['id'] ?? 0),
            'serie_nummer' => (int) ($stich['serie_nummer'] ?? 1),
            'bezeichnung' => (string) ($stich['name'] ?? 'Stich'),
            'kurzname' => (string) ($stich['short_name'] ?? ''),
            'externe_nummer' => self::externalNumber($stich['anzeige_id'] ?? null),
            'preis' => (float) ($stich['preis'] ?? 0),
            'anzahl_schuss' => max(1, $anzahlSchuss),
            'anzahl_stiche' => max(0, (int) ($stich['anzahl_stiche'] ?? 0)),
            'erwartet' => max(0, (int) ($stich['anzahl_schuss'] ?? 0) * max(0, (int) ($stich['anzahl_stiche'] ?? 0))),
            'total' => $total,
            'vollstaendig' => (int) ($stich['id'] ?? 0) > 0 && count($werte) === (int) ($stich['anzahl_schuss'] ?? 0),
            'schuesse' => $werte,
        ];
    }

    private static function externalNumber(mixed $value): string { return trim((string) ($value ?? '')); }
    private static function numericValue(mixed $value): float { return (float) str_replace(',', '.', (string) ($value ?? 0)); }
}
