<?php
declare(strict_types=1);
namespace App\Core;

use PDO;

/** Additive, resumable migration for the October 12 review corrections. */
final class ReviewMigration
{
    public function __construct(private readonly PDO $pdo) {}

    public function preflight(): void
    {
        $duplicates = $this->pdo->query('SELECT id_anlass, TRIM(anzeige_id) AS nummer, COUNT(*) AS anzahl FROM stich WHERE NULLIF(TRIM(anzeige_id), "") IS NOT NULL GROUP BY id_anlass, TRIM(anzeige_id) HAVING COUNT(*) > 1')->fetchAll();
        if ($duplicates !== []) throw new \RuntimeException('Mehrdeutige Anzeige-IDs müssen vor der Migration korrigiert werden: ' . json_encode($duplicates, JSON_UNESCAPED_UNICODE));
        $orphans = $this->pdo->query('SELECT COUNT(*) FROM gaben_abgaben ga LEFT JOIN gaben g ON g.id = ga.gaben_id LEFT JOIN stich st ON st.id = ga.stich_id LEFT JOIN standblatt s ON s.id = ga.standblatt_id WHERE g.id IS NULL OR st.id IS NULL OR s.id IS NULL')->fetchColumn();
        if ((int) $orphans > 0) throw new \RuntimeException('Abgaben mit fehlenden Stammdaten müssen vor der Migration geprüft werden.');
        $unallocated = $this->pdo->query('SELECT COUNT(*) FROM standblatt s WHERE s.kosten > 0 AND NOT EXISTS (SELECT 1 FROM standblatt_stich ss WHERE ss.id_standblatt = s.id)')->fetchColumn();
        if ((int) $unallocated > 0) throw new \RuntimeException('Standblattkosten ohne gelöste Stiche können nicht zugeordnet werden und müssen vor der Migration geprüft werden.');
        foreach (['standblatt' => 'kosten', 'stich' => 'preis', 'gaben' => 'preis'] as $table => $column) {
            if ((int) $this->pdo->query('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $column . ' < 0')->fetchColumn() > 0) {
                throw new \RuntimeException('Negative Preise in ' . $table . ' müssen vor der Migration geprüft werden.');
            }
        }
    }

    private function columns(string $table): array
    {
        return array_column($this->pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(), 'Field');
    }

    private function add(string $table, string $column, string $definition): void
    {
        if (!in_array($column, $this->columns($table), true)) $this->pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }

    public static function allocate(int $cents, array $weights): array
    {
        if ($weights === []) return [];
        $total = array_sum($weights);
        if ($total <= 0) { $weights = array_fill_keys(array_keys($weights), 1); $total = count($weights); }
        $result = [];
        foreach ($weights as $id => $weight) $result[$id] = (int) floor($cents * ($weight / $total));
        $remainder = $cents - array_sum($result);
        arsort($weights, SORT_NUMERIC);
        $first = array_key_first($weights);
        $result[$first] += $remainder;
        return $result;
    }

    public function apply(): void
    {
        try { $this->pdo->query('SHOW CREATE TABLE app_migrations'); }
        catch (\PDOException $error) {
            if (($error->errorInfo[1] ?? 0) !== 1146) throw $error;
            $this->pdo->exec('CREATE TABLE app_migrations (id VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        }
        if ($this->pdo->query("SELECT COUNT(*) FROM app_migrations WHERE id = 'review_20261012'")->fetchColumn()) return;
        $this->preflight();
        foreach (['preis' => 'DECIMAL(12,6) NULL', 'kosten' => 'DECIMAL(10,2) NULL', 'preis_quelle' => "VARCHAR(20) NOT NULL DEFAULT 'erfasst'"] as $key => $type) $this->add('standblatt_stich', $key, $type);
        foreach (['name_snapshot' => 'VARCHAR(150) NULL', 'preis_snapshot' => 'DECIMAL(10,2) NULL', 'punktwert_snapshot' => 'DECIMAL(10,2) NULL', 'anzahl_snapshot' => 'INT UNSIGNED NULL', 'ist_geld_snapshot' => 'TINYINT(1) NULL', 'stich_name_snapshot' => 'VARCHAR(150) NULL', 'preis_quelle' => "VARCHAR(20) NOT NULL DEFAULT 'erfasst'"] as $key => $type) $this->add('gaben_abgaben', $key, $type);
        $this->add('schussdaten', 'manuell_geloescht', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->pdo->exec('ALTER TABLE schussdaten MODIFY time_since_change DECIMAL(14,4) UNSIGNED NULL');
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        preg_match('/CREATE TABLE gaben_bewegungen \([\s\S]*?\n\);/', $schema, $match);
        // SHOW CREATE detects connection-local tables too; tests cannot touch persistent ones.
        try { $this->pdo->query('SHOW CREATE TABLE gaben_bewegungen'); }
        catch (\PDOException $error) {
            if (($error->errorInfo[1] ?? 0) !== 1146) throw $error;
            $this->pdo->exec($match[0]);
        }
        $this->pdo->beginTransaction();
        try {
            $rows = $this->pdo->query('SELECT ss.id, ss.id_standblatt, ss.anzahl_stiche, st.preis, s.kosten AS gesamtkosten FROM standblatt_stich ss INNER JOIN stich st ON st.id = ss.id_stich INNER JOIN standblatt s ON s.id = ss.id_standblatt WHERE ss.preis IS NULL OR ss.kosten IS NULL ORDER BY ss.id')->fetchAll();
            $sheets = [];
            foreach ($rows as $row) $sheets[(int) $row['id_standblatt']][] = $row;
            $update = $this->pdo->prepare("UPDATE standblatt_stich SET anzahl_stiche = ?, preis = ?, kosten = ?, preis_quelle = 'uebernommen' WHERE id = ?");
            foreach ($sheets as $purchases) {
                $weights = [];
                foreach ($purchases as $row) $weights[(int) $row['id']] = (float) ($row['preis'] ?? 0) * max(1, (int) $row['anzahl_stiche']);
                if (array_sum($weights) <= 0) foreach ($purchases as $row) $weights[(int) $row['id']] = max(1, (int) $row['anzahl_stiche']);
                $costs = self::allocate((int) round((float) ($purchases[0]['gesamtkosten'] ?? 0) * 100), $weights);
                foreach ($purchases as $row) {
                    $count = max(1, (int) $row['anzahl_stiche']);
                    $cost = $costs[(int) $row['id']] / 100;
                    $update->execute([$count, number_format($cost / $count, 6, '.', ''), number_format($cost, 2, '.', ''), $row['id']]);
                }
            }
            $this->pdo->exec("UPDATE gaben_abgaben ga INNER JOIN gaben g ON g.id = ga.gaben_id INNER JOIN stich st ON st.id = ga.stich_id SET ga.name_snapshot = g.name, ga.preis_snapshot = g.preis, ga.punktwert_snapshot = g.punktwert, ga.anzahl_snapshot = g.anzahl, ga.ist_geld_snapshot = LOCATE('barauszahlung', LOWER(g.name)) > 0, ga.stich_name_snapshot = st.name, ga.preis_quelle = 'uebernommen' WHERE ga.preis_snapshot IS NULL");
            $deliveries = $this->pdo->query('SELECT * FROM gaben_abgaben WHERE abgegeben = 1')->fetchAll();
            $known = $this->pdo->prepare('SELECT 1 FROM gaben_bewegungen WHERE gaben_id = ? AND standblatt_id = ? AND stich_id = ? AND serie_nummer = ? LIMIT 1');
            $movement = $this->pdo->prepare("INSERT INTO gaben_bewegungen (gaben_id, standblatt_id, stich_id, serie_nummer, richtung, name_snapshot, preis_snapshot, ist_geld_snapshot, preis_quelle, created_by_user_id) VALUES (?, ?, ?, ?, 1, ?, ?, ?, 'uebernommen', ?)");
            foreach ($deliveries as $row) {
                $identity = [$row['gaben_id'], $row['standblatt_id'], $row['stich_id'], $row['serie_nummer']];
                $known->execute($identity);
                if (!$known->fetchColumn()) $movement->execute(array_merge($identity, [$row['name_snapshot'], $row['preis_snapshot'], $row['ist_geld_snapshot'], $row['updated_by_user_id']]));
            }
            $this->pdo->exec('UPDATE stich SET anzeige_id = NULLIF(TRIM(anzeige_id), "")');
            // Changed scoring semantics require a fresh check; confirmed deliveries stay intact.
            $this->pdo->exec('UPDATE standblatt SET gaben_geprueft = 0');
            $this->pdo->exec('DELETE FROM gaben_abgaben WHERE abgegeben = 0');
            $this->pdo->commit();
        } catch (\Throwable $error) { $this->pdo->rollBack(); throw $error; }
        $this->pdo->exec('ALTER TABLE standblatt_stich MODIFY preis DECIMAL(12,6) NOT NULL, MODIFY kosten DECIMAL(10,2) NOT NULL, MODIFY anzahl_stiche INT UNSIGNED NOT NULL DEFAULT 1');
        $this->pdo->exec('ALTER TABLE gaben_abgaben MODIFY name_snapshot VARCHAR(150) NOT NULL, MODIFY preis_snapshot DECIMAL(10,2) NOT NULL, MODIFY punktwert_snapshot DECIMAL(10,2) NOT NULL, MODIFY anzahl_snapshot INT UNSIGNED NOT NULL, MODIFY ist_geld_snapshot TINYINT(1) NOT NULL, MODIFY stich_name_snapshot VARCHAR(150) NOT NULL');
        foreach (['fk_standblatt_stich_stich' => ['standblatt_stich', 'id_stich', 'stich'], 'fk_gaben_abgaben_gaben' => ['gaben_abgaben', 'gaben_id', 'gaben'], 'fk_gaben_abgaben_standblatt' => ['gaben_abgaben', 'standblatt_id', 'standblatt'], 'fk_gaben_abgaben_stich' => ['gaben_abgaben', 'stich_id', 'stich'], 'fk_auszeichnungslimiten_gaben' => ['auszeichnungslimiten', 'gaben_id', 'gaben']] as $constraint => [$table, $column, $parent]) {
            $this->protectReference($table, $column, $parent, $constraint);
        }
        $indexes = array_column($this->pdo->query('SHOW INDEX FROM stich')->fetchAll(), 'Key_name');
        if (!in_array('uq_stich_anlass_anzeige', $indexes, true)) $this->pdo->exec('ALTER TABLE stich ADD UNIQUE KEY uq_stich_anlass_anzeige (id_anlass, anzeige_id)');
        $this->pdo->exec("INSERT INTO app_migrations (id) VALUES ('review_20261012')");
    }

    /** Keep replacement atomic while avoiding MariaDB's same-name FK collision. */
    public function protectReference(string $table, string $column, string $parent, string $constraint): void
    {
        foreach ([$table, $column, $parent, $constraint] as $name) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) throw new \InvalidArgumentException('Ungültiger Schema-Bezeichner.');
        }
        $definition = $this->pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch()['Create Table'];
        // Temporary test tables deliberately have no foreign keys.
        if (str_starts_with($definition, 'CREATE TEMPORARY TABLE')) return;
        $drop = '';
        if (preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`' . $column . '`\) REFERENCES `' . $parent . '` \(`id`\)/', $definition, $old)) {
            $rule = $this->pdo->prepare('SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?');
            $rule->execute([$table, $old[1]]);
            if (in_array($rule->fetchColumn(), ['RESTRICT', 'NO ACTION'], true)) return;
            $drop = 'DROP FOREIGN KEY `' . $old[1] . '`, ';
            // MariaDB cannot drop and add the same FK name in one atomic ALTER.
            if ($constraint === $old[1]) $constraint .= '_review';
        }
        $this->pdo->exec('ALTER TABLE `' . $table . '` ' . $drop . 'ADD CONSTRAINT `' . $constraint . '` FOREIGN KEY (`' . $column . '`) REFERENCES `' . $parent . '` (`id`) ON DELETE RESTRICT');
    }
}
