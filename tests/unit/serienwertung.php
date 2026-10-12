<?php

declare(strict_types=1);

namespace App\Models {
    class Stich {
        public function findByAnlassId(int $id): array {
            return [['id'=>1, 'name'=>'Chilbistich', 'anzeige_id'=>901, 'anzahl_schuss'=>5]];
        }
    }
    class Standblatt {
        public function findById(int $id): array {return ['geburtsdatum'=>'2013-07-23','start_anlass'=>'2026-10-11'];}
        public function findSticheForAnlass(int $id): array {return [24=>$this->findSticheForStandblatt(24)];}

        public function findForAnlassWithAdresse(int $id): array {
            return [['id'=>24,'vorname'=>'Cheryl','nachname'=>'Ruoss','geburtsdatum'=>'2013-07-23','final_teilnahme'=>1]];
        }
        public function findSticheForStandblatt(int $id): array {
            return [['id'=>1,'name'=>'Chilbistich','anzeige_id'=>901,'anzahl_schuss'=>5,'anzahl_stiche'=>5]];
        }
    }
    class Schussdaten {
        public function findByAnlassId(int $id): array {
            $rows=[];
            $series=[[9,10,10,9,10],[10,10,9,9,9],[10,10,10,9,10],[10,9,9,10,9],[10,9,10,10,8]];
            foreach ($series as $serie=>$values) {
                foreach ($values as $index=>$value) {
                    $rows[]=['id'=>1000-($serie*5+$index),'schuss_zeit'=>sprintf('2026-10-11 10:00:%02d',$serie*5+$index),'start_nr'=>24,'primaerwertung'=>$value,'externe_nummer'=>901,'match_index'=>$index+1,'stich_index'=>1];
                }
            }
            $rows[]=['id'=>999,'start_nr'=>24,'primaerwertung'=>10,'externe_nummer'=>901,'manuell_geloescht'=>1];
            usort($rows,static fn($a,$b)=>($a['match_index'] ?? 0) <=> ($b['match_index'] ?? 0));
            return $rows;
        }
        public function findByStartNrAndIdAnlass(int $id,int $anlass): array { return array_reverse($this->findByAnlassId($anlass)); }
    }
    class Gaben {
        public function findRegelnForStiche(array $ids): array { return []; }
        public function findAbgabenForStandblatt(int $id): array { return []; }
        public function areAbgabenGeprueft(int $id): bool { return false; }
    }
}
namespace {
    require dirname(__DIR__, 2).'/app/Services/Schussreihenfolge.php';
    require dirname(__DIR__, 2).'/app/Services/SchussStatus.php';
    require dirname(__DIR__, 2).'/app/Services/SerienService.php';
    require dirname(__DIR__, 2).'/app/Services/Altersregel.php';
    require dirname(__DIR__, 2).'/app/Services/RanglistenService.php';
    require dirname(__DIR__, 2).'/app/Services/AbrechnungsService.php';
    $standblatt=new \App\Models\Standblatt();
    $shots=new \App\Models\Schussdaten();
    $rank=new \App\Services\RanglistenService(new \App\Models\Stich(),$standblatt,$shots);
    $rechnung=new \App\Services\AbrechnungsService($standblatt,$shots,new \App\Models\Gaben());
    $row=$rank->buildForAnlass(1,'2026-10-11')[0]['teilnehmer'][0];
    $totals=array_column($rechnung->buildViewData(1,24)['auswertung']['rows'],'total');
    rsort($totals,SORT_NUMERIC);
    $check=static function(bool $ok,string $message): void { if (!$ok) throw new \RuntimeException($message); };
    $check($row['resultate']===[49.0,48.0,47.0,47.0,47.0],'Correct series despite restarted counters');
    $check($row['resultate']===$totals,'Ranking matches standblatt');
    $check($row['total']===49.0 && $row['summe_beste_zwei']===97.0,'Best result and best two');
    $check($row['schuss_count']===25,'Deleted shots excluded');
    echo "Serienwertung passed: stable series, ranking/billing parity, best two and deleted shots.\n";
}
