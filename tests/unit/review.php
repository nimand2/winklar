<?php
declare(strict_types=1);
putenv('APP_BASE_PATH=/review-app');
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$bad = App\Validation\AnlassFormService::sanitizeStichData(['name'=>'Test','preis'=>'-10','anzahl_schuss'=>'abc'], 1, 1);
$check(App\Validation\AnlassFormService::validateStichData($bad) !== [], 'Malformed amounts and shot counts rejected');
$badDate = App\Validation\AnlassFormService::sanitizeAnlassData(['name_anlass'=>'Test','start_anlass'=>'2026-02-30'],1);
$check(App\Validation\AnlassFormService::validateAnlassData($badDate) !== [], 'Nonexistent date rejected');
$check(App\Validation\FormInput::timestamp('2026-10-12T10:00:59') === '2026-10-12 10:00:59','Seconds preserved');
foreach (['2026-10-12T24:00:00', '2026-02-30T10:00', '10:00:00', '2026-10-12T10:00:00+14:01', '2026-10-12T10:00:00+01:60'] as $time) {
    try { App\Validation\FormInput::timestamp($time); throw new LogicException('Invalid time accepted'); } catch (InvalidArgumentException) {}
}
foreach (['', '{oops', '[]', 'null', '1'] as $json) {
    try { App\Core\JsonRequest::decode($json); throw new LogicException('Invalid JSON accepted'); } catch (InvalidArgumentException) {}
}
$check(App\Core\JsonRequest::decode('{"Shots":[]}') === ['Shots'=>[]], 'Explicit empty list remains a valid object');
$check(App\Services\SchussStatus::istAktiv(['ins_del'=>1]),'SIUS inserted shots count');
$check(!App\Services\SchussStatus::istAktiv(['ins_del'=>2]),'SIUS ignored shots do not count');
$check(!App\Services\SchussStatus::istAktiv(['ins_del'=>1,'manuell_geloescht'=>1]),'Local deletion is separate');
$stich = ['id'=>1,'name'=>'Test','anzeige_id'=>'901','anzahl_schuss'=>5,'anzahl_stiche'=>1];
$shots = [];
for ($i=1;$i<=10;$i++) $shots[]=['id'=>$i,'externe_nummer'=>'901','primaerwertung'=>$i<=5?1:10,'ins_del'=>1];
$series = App\Services\SerienService::build([$stich],$shots)['rows'];
$check(count($series)===2 && $series[0]['total']===5.0 && $series[0]['vollstaendig'] && $series[1]['stich_id']===0,'Only purchased series assigned');
$partial=App\Services\SerienService::build([$stich],array_slice($shots,0,4))['rows'][0];
$check(!$partial['vollstaendig'],'Partial series never complete');
$check(App\Services\SerienService::build([array_replace($stich,['anzahl_stiche'=>0])],$shots)['rows'][0]['stich_id']===0,'Zero purchases never count');
$check(App\Services\Altersregel::alter('2009-12-31','2026-10-12')===17,'Year-based age');
$check(App\Services\Altersregel::alter('2027-01-01','2026-10-12')===null,'Future birth year rejected');
$form=App\Validation\StandblattFormService::parse(['datum'=>'2026-10-12','stich_ids'=>['1'],'stich_counts'=>[1=>'1001']],[$stich]);
$check($form['errors']!==[],'Excessive purchase count rejected');
$form=App\Validation\StandblattFormService::parse(['stich_ids'=>[[]]],[$stich]);
$check($form['errors']!==[],'Array-shaped ID safely rejected');
$router = new App\Core\Router();$called=false;
$router->get('/dashboard', static function() use(&$called): void {$called=true;});
$router->dispatch('GET',App\Core\Url::app('/dashboard'));
$check($called,'Generated base-path URL reaches handler');
$weights=[1=>10,2=>20,3=>30];
$costs=App\Core\ReviewMigration::allocate(1001,$weights);
$check(array_sum($costs)===1001 && min($costs)>=0,'Legacy allocation conserves cents');
$check(array_sum(App\Core\ReviewMigration::allocate(1,[1=>0,2=>0,3=>0]))===1,'Zero-price legacy allocation');
$directory=sys_get_temp_dir().'/winklar-limiter-test-'.bin2hex(random_bytes(8));
$limiter=new App\Core\LoginLimiter($directory);
try {
    for($i=0;$i<10;$i++) $check($limiter->attempt('Test','127.0.0.1',1000),'Allowed login attempt');
    $check(!$limiter->attempt('test','127.0.0.1',1001),'Case-insensitive cross-session limit');
    $check($limiter->attempt('Test','127.0.0.1',1901),'Limit expires');
} finally { unlink($directory.'/limits.json');rmdir($directory); }
echo "Review unit checks passed: strict input, JSON, SIUS flags, paid/full series, age, routing, prices and login limits.\n";
