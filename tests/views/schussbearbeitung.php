<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . "/app/Core/Session.php";
require dirname(__DIR__, 2) . "/app/Core/Csrf.php";
define("REMEMBER_ME_SECURE_COOKIE", false);
session_save_path(sys_get_temp_dir());
\App\Core\Session::start();

require dirname(__DIR__, 2) . '/app/Core/Url.php';
require dirname(__DIR__, 2) . '/app/Core/View.php';
define('APP_BASE_PATH', '');

ob_start();
\App\Core\View::partial('abrechnen/abrechnen', [
    'anlass' => ['id' => 1, 'name_anlass' => 'Test'],
    'standblatt' => ['id' => 2, 'datum' => '2026-10-11'],
    'adresse' => ['vorname' => 'Test', 'nachname' => 'Person'],
    'stiche' => [
        ['anzeige_id' => 901, 'name' => 'Chilbistich'],
        ['anzeige_id' => 902, 'name' => 'Übungsstich'],
    ],
    'auswertung' => ['rows' => [[
        'bezeichnung' => 'Übungsstich', 'externe_nummer' => '902',
        'anzahl_schuss' => 5, 'total' => 9,
        'schuesse' => [['id' => 4230, 'primaerwertung' => 9, 'externe_nummer' => '902']],
    ]]],
]);
$html = ob_get_clean();
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$check(substr_count($html, 'Alle Schüsse speichern') === 1, 'One save button per series');
$check(str_contains($html, '/abrechnen/schuesse'), 'Batch save route');
$check(substr_count($html, 'value="902" selected') === 5, 'Numeric target keys select the practice target for existing and empty shots');
foreach (range(0, 4) as $index) {
    foreach (['id', 'primaerwertung', 'externe_nummer', 'schuss_zeit', 'mouche'] as $field) {
        $check(str_contains($html, 'name="schuesse[' . $index . '][' . $field . ']"'), 'All shot fields belong to the batch');
    }
}
$check(!str_contains($html, 'schuss-update-'), 'No individual save forms');
$check(str_contains($html, '/schuss/4230/loeschen'), 'Existing shot deletion remains available');
echo "Schussbearbeitung passed: batch fields, five slots, practice target selection and deletion.\n";
