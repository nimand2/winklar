<?php
declare(strict_types=1);
require dirname(__DIR__).'/support/database.php';
$pdo=review_test_database();review_test_seed($pdo);
$check=static function(bool $ok,string $message): void {if(!$ok) throw new RuntimeException($message);};
// Simulate the legacy schema inside the shadow tables only.
foreach (['preis','kosten','preis_quelle'] as $column) $pdo->exec('ALTER TABLE standblatt_stich DROP COLUMN '.$column);
foreach (['name_snapshot','preis_snapshot','punktwert_snapshot','anzahl_snapshot','ist_geld_snapshot','stich_name_snapshot','preis_quelle'] as $column) $pdo->exec('ALTER TABLE gaben_abgaben DROP COLUMN '.$column);
$pdo->exec('ALTER TABLE schussdaten DROP COLUMN manuell_geloescht, MODIFY time_since_change INT UNSIGNED NULL');
$pdo->exec('ALTER TABLE stich DROP INDEX uq_stich_anlass_anzeige');
$pdo->exec("INSERT INTO stich(id,id_anlass,name,anzeige_id,preis,anzahl_schuss) VALUES(6,1,'Extra','906',3,5),(7,1,'Duplicate','901',1,5)");
$pdo->exec('INSERT INTO standblatt_stich(id_standblatt,id_stich,anzahl_stiche) VALUES(1,6,3)');
$pdo->exec('UPDATE standblatt SET kosten=23.01,gaben_geprueft=1 WHERE id=1');
$pdo->exec('UPDATE gaben_abgaben SET abgegeben=1 WHERE id=1');
$pdo->exec('UPDATE schussdaten SET ins_del=1 WHERE id=1');
$migration=new App\Core\ReviewMigration($pdo);
try {$migration->preflight();throw new LogicException('Ambiguous IDs accepted');}catch(RuntimeException $e){$check(str_contains($e->getMessage(),'Anzeige-IDs'),'Duplicate mappings stopped before changes');}
$pdo->exec('DELETE FROM stich WHERE id=7');
$migration->apply();
$sum=(float)$pdo->query('SELECT SUM(kosten) FROM standblatt_stich WHERE id_standblatt=1')->fetchColumn();
$check($sum===23.01,'Historical allocation preserves stored total exactly');
$check((int)$pdo->query("SELECT COUNT(*) FROM standblatt_stich WHERE preis_quelle='uebernommen'")->fetchColumn()===6,'Imported prices labeled');
$check((float)$pdo->query('SELECT preis_snapshot FROM gaben_abgaben WHERE id=1')->fetchColumn()===10.0,'Existing gift price copied');
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_abgaben')->fetchColumn()===1,'Confirmed delivery retained; open entitlements need fresh check');
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_bewegungen')->fetchColumn()===1,'Existing confirmation recorded in ledger');
$shot=(new App\Models\Schussdaten())->findById(1);
$check(App\Services\SchussStatus::istAktiv($shot),'Existing SIUS 1 flags restored per user confirmation');
$pdo->exec('UPDATE gaben SET preis=99 WHERE id=1');
$check((float)(new App\Models\Gaben())->findAbgabenForStandblatt(1)[0]['preis']===10.0,'Migrated gift value independent of future catalog changes');
$pdo->exec('UPDATE standblatt SET gaben_geprueft=1 WHERE id=1');
$migration->apply();
$check((int)$pdo->query('SELECT gaben_geprueft FROM standblatt WHERE id=1')->fetchColumn()===1,'Repeated migration makes no changes');
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_bewegungen')->fetchColumn()===1,'Repeated migration never duplicates history');
echo "Review migration checks passed: preflight, legacy price allocation, preserved deliveries, restored SIUS flags and idempotence.\n";
