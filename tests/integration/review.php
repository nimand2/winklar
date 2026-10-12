<?php
declare(strict_types=1);
require dirname(__DIR__).'/support/database.php';
$pdo=review_test_database();
review_test_seed($pdo);
$check=static function(bool $ok,string $message): void {if(!$ok) throw new RuntimeException($message);};
$sheet=new App\Models\Standblatt();$gifts=new App\Models\Gaben();$shots=new App\Models\Schussdaten();$stichModel=new App\Models\Stich();
$check((int)$pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn()===39,'Complete seed compatible with schema');
$check((int)$pdo->query('SELECT COUNT(*) FROM standblatt_stich WHERE anzahl_stiche=1 AND kosten=preis')->fetchColumn()===5,'Seed purchases/prices consistent');
$check(!$gifts->delete(1),'Referenced gift cannot be deleted');
$check(!$stichModel->delete(1),'Purchased stich cannot be deleted');
$old=$sheet->findById(1)['kosten'];
$pdo->exec('UPDATE stich SET preis=99 WHERE id=1');
$check((float)$sheet->findEinnahmenByStichForAnlass(1)[0]['total']===(float)$old,'Revenue uses booked price');
$sheet->updateWithStiche(1,['id_anlass'=>1,'id_adresse'=>1,'datum'=>'2026-04-11','kosten'=>999,'updated_by_user_id'=>1],[1=>1],1);
$check((float)$sheet->findById(1)['kosten']===(float)$old,'Unchanged quantities never repriced');
$sheet->updateWithStiche(1,['id_anlass'=>1,'id_adresse'=>1,'datum'=>'2026-04-11','kosten'=>999,'updated_by_user_id'=>1],[1=>2],1);
$check((float)$sheet->findById(1)['kosten']===36.0,'Increased quantities retain the booked rate');
$pdo->exec('UPDATE gaben SET name="Barauszahlung",preis=10 WHERE id=1');
$snapshot=['name'=>'Barauszahlung','preis'=>10,'punktwert'=>85,'anzahl'=>1,'ist_geld'=>true,'stich_name'=>'Vereinsstich'];
$item=['gaben_id'=>1,'stich_id'=>1,'serie_nummer'=>1,'abgegeben'=>1,'snapshot'=>$snapshot];
$gifts->replaceAbgabenForStandblatt(1,[$item,$item],1);
$check(count($gifts->findAbgabenForStandblatt(1))===1,'Repeated matching rule saves one award');
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_bewegungen')->fetchColumn()===1,'One confirmation ledger entry');
$pdo->exec('UPDATE gaben SET name="Anderer Name",preis=20 WHERE id=1');
$stored=$gifts->findAbgabenForStandblatt(1)[0];
$check((float)$stored['preis']===10.0 && $stored['name']==='Barauszahlung' && (int)$stored['ist_geld']===1,'Delivered snapshots immutable');
$gifts->replaceAbgabenForStandblatt(1,[$item],1);
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_bewegungen')->fetchColumn()===1,'Repeated save never rebooks payment');
$cash=(new App\Services\KassenService($sheet,$gifts))->buildAbrechnung(1);
$check($cash['geld_abgegeben']===10.0 && $cash['gaben_total']===10.0,'Confirmed cash deducted at original price');
$return=array_replace($item,['abgegeben'=>0,'ruecknahme'=>true]);
$gifts->replaceAbgabenForStandblatt(1,[$return],1);
$check((int)$pdo->query('SELECT SUM(richtung) FROM gaben_bewegungen')->fetchColumn()===0,'Return retained in ledger');
$cash=(new App\Services\KassenService($sheet,$gifts))->buildAbrechnung(1);
$check($cash['gaben_total']===0.0 && $cash['geld_abgegeben']===0.0 && $cash['geld_offen']===10.0,'Open payout never reduces cash');
$gifts->resetAbgabenForStandblatt(1,1);
$check((int)$pdo->query('SELECT COUNT(*) FROM gaben_bewegungen')->fetchColumn()===2,'Reset preserves payment and return history');
$gifts->replaceAbgabenForStandblatt(1,[$item],1);
$api=new App\Services\ClientApiService(new App\Models\Anlass(),$sheet,$shots);
$valid=['StartNr'=>1,'Primaerwertung'=>9.0,'Zeit'=>'2026-04-10 11:00:00.190','ExterneNummer'=>901,'InsDel'=>1,'TimeSinceChange'=>2.5];
$check($api->importShots(1,[$valid],1)===1,'V2 numeric payload and inserted flag imported');
$check(!$gifts->areAbgabenGeprueft(1),'New import invalidates awards atomically');
$inserted=$shots->findByStartNrAndIdAnlass(1,1)[0];
$check((float)$inserted['time_since_change']===2.5,'Fractional timing preserved');
$check(App\Services\SchussStatus::istAktiv($inserted),'SIUS inserted record is scored');
$gifts->replaceAbgabenForStandblatt(1,[$item],1);
$check($api->importShots(1,[$valid],1)===0 && $gifts->areAbgabenGeprueft(1),'Duplicate replay leaves award status intact');
$shots->markDeleted((int)$inserted['id'],1);
$check(!App\Services\SchussStatus::istAktiv($shots->findById((int)$inserted['id'])),'Manual deletion is local');
$check($api->importShots(1,[$valid],1)===0 && (int)$shots->findById((int)$inserted['id'])['manuell_geloescht']===1,'Replay never revives local deletion');
$before=(int)$pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn();
foreach ([array_replace($valid,['StartNr'=>2]),array_replace($valid,['Primaerwertung'=>'oops']),array_replace($valid,['primaerwertung'=>[]]),[]] as $bad) {
    try {$api->importShots(1,[$valid,$bad],1);throw new LogicException('Invalid batch accepted');}catch(InvalidArgumentException){}
}
$check((int)$pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn()===$before,'Invalid batches change no data');
try {$pdo->exec("INSERT INTO stich(id_anlass,name,anzeige_id,anzahl_schuss) VALUES(1,'Duplicate','901',5)");throw new LogicException('Duplicate ID accepted');}catch(PDOException $e){$check(($e->errorInfo[1]??null)===1062,'Unique SIUS mapping');}
$pdo->exec("INSERT INTO auszeichnungslimiten(stich_id,gaben_id,min_wert,max_wert,min_alter,max_alter) VALUES(1,1,0,100,0,17)");
$billing=new App\Services\AbrechnungsService($sheet,$shots,$gifts);
$lastRule=(int)$pdo->query('SELECT MAX(id) FROM auszeichnungslimiten')->fetchColumn();
$pdo->exec('DELETE FROM auszeichnungslimiten WHERE id <> '.$lastRule);
$gifts->resetAbgabenForStandblatt(1,1);
$pdo->exec('DELETE FROM gaben_abgaben WHERE standblatt_id=1');
$check($billing->buildViewData(1,1)['gabenVergleich']===[],'Adult receives no U18-only award');
$pdo->exec("UPDATE adressen SET geburtsdatum='2010-01-01' WHERE id=1");
$check(count($billing->buildViewData(1,1)['gabenVergleich'])===1,'Correct age receives eligible award');
$pdo->exec('INSERT INTO auszeichnungslimiten(stich_id,gaben_id,min_wert,max_wert,min_alter,max_alter) VALUES(1,1,0,100,0,17)');
$check(count($billing->buildViewData(1,1)['gabenVergleich'][0]['gaben'])===1,'Overlapping rules deduplicated');
$rank=(new App\Services\RanglistenService($stichModel,$sheet,$shots))->buildForAnlass(1,'2026-04-10');
$check(count($rank[0]['teilnehmer'][0]['resultate'])===1,'Incomplete second purchased series excluded');
$final=new App\Services\FinalService(new App\Services\RanglistenService($stichModel,$sheet,$shots),$sheet);
$anlass=['id'=>1,'name_anlass'=>'Test','start_anlass'=>'2026-04-10','final_stich_id'=>1,'final_anzahl_u18'=>6,'final_anzahl_ue18'=>6];
$pdo->exec('UPDATE standblatt SET final_teilnahme=1 WHERE id=1');
$check($final->buildForAnlass($anlass)['teilnehmer']===[],'Final requires two complete paid series');
// Complete the second paid series and send an extra, unpaid series.
$batch=[];
for($i=0;$i<10;$i++) $batch[]=array_replace($valid,['Zeit'=>'2026-04-10 12:00:'.sprintf('%02d',$i),'Primaerwertung'=>10,'LogEvent'=>90000+$i]);
$api->importShots(1,$batch,1);
$rank=(new App\Services\RanglistenService($stichModel,$sheet,$shots))->buildForAnlass(1,'2026-04-10');
$check(count($rank[0]['teilnehmer'][0]['resultate'])===2,'Only the two purchased complete series rank, even with extra shots');
$check(count($final->buildForAnlass($anlass)['teilnehmer'])===1,'Two complete paid series permit final participation');
$before=(int)$pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn();
$pdo->exec('ALTER TABLE standblatt CHANGE gaben_geprueft temporarily_unavailable TINYINT(1) NOT NULL DEFAULT 0');
try {
    $api->importShots(1,[array_replace($valid,['Zeit'=>'2026-04-10 13:00:01'])],1);
    throw new LogicException('Award-reset failure accepted');
} catch(PDOException $error) {
    $check(($error->errorInfo[1]??0)===1054,'Expected injected award-reset failure');
} finally {
    $pdo->exec('ALTER TABLE standblatt CHANGE temporarily_unavailable gaben_geprueft TINYINT(1) NOT NULL DEFAULT 0');
}
$check((int)$pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn()===$before,'Award-reset failure rolls back imported shots too');
echo "Review database checks passed: seed, historical prices, protected deletion, ledger, cash, atomic import, replay, input, age, duplicates and final eligibility.\n";
