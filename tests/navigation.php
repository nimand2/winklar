<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/Core/Navigation.php';
require dirname(__DIR__) . '/app/Core/Url.php';
require dirname(__DIR__) . '/app/Core/View.php';
define('APP_BASE_PATH', '/test-app');
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$anlass = ['id'=>9,'name_anlass'=>'Test <Anlass>','shortname_anlass'=>'Test','start_anlass'=>'2026-10-11','end_anlass'=>'2026-10-11'];
$sheet = ['id'=>16,'datum'=>'2026-10-11','kosten'=>10];
$adresse = ['id'=>4,'vorname'=>'Test','nachname'=>'Person','email'=>'','telefon'=>''];
$cases = [
    ['dashboard/index', ['user'=>['username'=>'Test']], null],
    ['anlass/index', ['anlass'=>[$anlass]], null],
    ['anlass/form', ['mode'=>'create'], null],
    ['anlass/form', ['anlass'=>$anlass,'mode'=>'edit'], 'konfiguration'],
    ['anlass/show', ['anlass'=>$anlass], 'uebersicht'],
    ['anlass/konfiguration', ['anlass'=>$anlass], 'konfiguration'],
    ['anlass/rangliste', ['anlass'=>$anlass], 'rangliste'],
    ['anlass/kasse', ['anlass'=>$anlass], 'kasse'],
    ['anlass/final', ['anlass'=>$anlass,'final'=>['stich'=>null]], 'final'],
    ['clients/indes', [], null],
    ['clients/indes', ['anlass'=>$anlass], 'schuetzen'],
    ['clients/form', [], null],
    ['clients/form', ['anlass'=>$anlass,'adresse'=>$adresse], 'schuetzen'],
    ['loesen/loesenOpen', ['anlass'=>$anlass,'standblaetter'=>[array_merge($sheet,$adresse,['id'=>16])]], 'standblaetter'],
    ['loesen/adresseSelect', ['anlass'=>$anlass], 'neu'],
    ['loesen/loesenNew', ['anlass'=>$anlass,'adresse'=>$adresse], 'neu'],
    ['loesen/loesenEdit', ['anlass'=>$anlass,'standblatt'=>$sheet,'adresse'=>$adresse], 'standblaetter'],
    ['abrechnen/abrechnen', ['anlass'=>$anlass,'standblatt'=>$sheet,'adresse'=>$adresse], 'standblaetter'],
];
preg_match_all('~[$]router->get[(][\x27]([^\x27]+)[\x27]~', file_get_contents(dirname(__DIR__).'/public/index.php'), $routeMatches);
$routePatterns = array_map(static fn(string $route): string => '~^'.preg_replace('/[{][^}]+[}]/','[^/]+',$route).'$~', $routeMatches[1]);
$paths=['uebersicht'=>'','konfiguration'=>'/konfiguration','rangliste'=>'/abschliessen','kasse'=>'/kasse','final'=>'/final','schuetzen'=>'/schuetzen','standblaetter'=>'/loesen','neu'=>'/loesen/adresse'];
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool { throw new ErrorException($message,0,$severity,$file,$line); });
foreach($cases as [$view,$data,$active]) {
    $nav=App\Core\Navigation::forView($view,$data);
    ob_start();App\Core\View::render($view,$data);$html=ob_get_clean();
    $doc=new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath=new DOMXPath($doc);
    $styles=$xpath->query('//link[@rel="stylesheet" and contains(@href,"css/app.css?v=")]');
    $check($styles->length===1,$view.': versioned application CSS');
    $check(str_ends_with($styles->item(0)->getAttribute('href'),substr(hash_file('sha256',dirname(__DIR__).'/public/assets/css/app.css'),0,12)),$view.': stylesheet version matches current content');
    $skip=$xpath->query('//a[contains(@class,"visually-hidden-focusable") and @href="#main-content"]');
    $check($skip->length===1,$view.': skip link hidden until keyboard focus');

    $check($xpath->query('//nav[@aria-label="Hauptnavigation"]')->length===1,$view.': one main navigation');
    $check($xpath->query('//main[@id="main-content"]')->length===1,$view.': content target');
    $check($xpath->query('//nav[@aria-label="Seitenpfad"]//li[@aria-current="page"]')->length===1,$view.': current breadcrumb');
    $check($xpath->query('//nav[@aria-label="Hauptnavigation"]//a[contains(@href,"/logout")]')->length===1,$view.': one sign-out');
    $check($xpath->query('//a[contains(@href,"/logout")]')->length===1,$view.': no duplicated sign-out');
    foreach($xpath->query('//a[@href]') as $anchor) {
        $href=$anchor->getAttribute('href');
        $check(str_starts_with($href,'/test-app/')||str_starts_with($href,'#'),$view.': base path preserved');
        $check(trim($anchor->textContent)!=='',$view.': named navigation links');
        if(!str_starts_with($href,'#')) {
            $path=substr((string)parse_url($href,PHP_URL_PATH),strlen(APP_BASE_PATH));
            $matches=false;
            foreach($routePatterns as $pattern) if(preg_match($pattern,$path)) { $matches=true; break; }
            $check($matches,$view.': destination route exists: '.$path);
        }

    }
    if($active!==null) {
        $nodes=$xpath->query('//nav[@aria-label="Navigation im Anlass"]//a[@aria-current="page"]');
        $check($nodes->length===1,$view.': exactly one active event area');
        $check($nodes->item(0)->getAttribute('href')==='/test-app/anlass/9'.$paths[$active],$view.': correct active area');
        $check(str_contains($html,'Test &lt;Anlass&gt;'),$view.': escaped event name');
    } else {
        $check($xpath->query('//nav[@aria-label="Navigation im Anlass"]')->length===0,$view.': no accidental event context');
    }
    if($view==='loesen/loesenOpen') {
        $check(str_contains($html,'/test-app/anlass/9/loesen/16/abrechnen'), 'Direct accounting action');
        $check(str_contains($html,'/test-app/anlass/9/loesen/16/druck'), 'Direct print action');
    }
    if($nav['back']!==null) $check(str_contains($html,App\Core\Url::app($nav['back']['path'])),$view.': deterministic back destination');
}
$check(App\Core\Navigation::forView('auth/login',[])===[],'Login remains separate');
$check(App\Core\Navigation::forView('loesen/standblattPrint',[])===[],'Print remains separate');
echo 'Navigation checks passed for '.count($cases).' page contexts, active menus, breadcrumbs, base paths and direct standblatt actions.'.PHP_EOL;
