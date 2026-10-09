<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Standblatt;
use App\Models\Gaben;
use App\Services\AuthService;
use App\Services\AnlassService;
use App\Services\FinalService;

final class FinalController extends Controller
{
    public function __construct(private readonly AuthService $authService, private readonly AnlassService $anlassService, private readonly FinalService $finalService, private readonly Standblatt $standblattModel, private readonly Gaben $gabenModel)
    {
    }

    private function data(array $params): array
    {
        $this->authService->requireUser();
        $anlass = $this->anlassService->getAnlassById((int) ($params['id'] ?? 0));
        if ($anlass === null) {
            Response::notFound('Anlass nicht gefunden');
        }
        return ['anlass' => $anlass, 'final' => $this->finalService->buildForAnlass($anlass)];
    }

    public function show(array $params): void
    {
        $this->render('anlass/final', $this->data($params));
    }

    public function export(array $params): void
    {
        $data = $this->categoryData($params);
        $kategorie = $data['final']['kategorie'];
        $json = json_encode($this->finalService->exportData($data['anlass'], $data['final'], $kategorie), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="final-' . $kategorie . '-anlass-' . (int) $data['anlass']['id'] . '.json"');
        header('Cache-Control: no-store');
        echo $json;
    }

    public function druck(array $params): void
    {
        $data = $this->categoryData($params);
        $blaetter = [];
        foreach ($data['final']['teilnehmer'] as $row) {
            $blaetter[] = [
                'anlass' => $data['anlass'],
                'standblatt' => $row['standblatt'],
                'adresse' => $row['standblatt'],
                'stiche' => $this->standblattModel->findSticheForStandblatt((int) $row['standblatt_id']),
                'gaben' => [],
                'batchPrint' => true,
            ];
        }
        $gaben = $this->gabenModel->getAll();
        usort($gaben, static fn (array $a, array $b): int => (float) $a['punktwert'] <=> (float) $b['punktwert']);
        foreach ($blaetter as &$blatt) {
            $blatt['gaben'] = $gaben;
        }
        unset($blatt);
        $this->render('anlass/finalPrint', array_merge($data, ['blaetter' => $blaetter]));
    }

    private function categoryData(array $params): array
    {
        $data = $this->data($params);
        $kategorie = (string) ($params['kategorie'] ?? '');
        if (!in_array($kategorie, ['u18', 'ue18'], true)) {
            Response::notFound('Finalkategorie nicht gefunden');
        }
        $data['final'] = $this->finalService->forCategory($data['final'], $kategorie);

        return $data;
    }
}
