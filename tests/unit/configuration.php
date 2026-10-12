<?php
declare(strict_types=1);
namespace App\Core {
    final class Response {
        public static function notFound(string $message='404'): never {throw new \OutOfBoundsException($message);}
        public static function redirect(string $path): never {throw new \LogicException('redirect:'.$path);}
    }
}
namespace App\Services {
    class AuthService {public function requireUser(): array {return ['id'=>1];}}
    class AnlassService {public function getAnlassById(int $id): array {return ['id'=>$id,'name_anlass'=>'Test'];}}
}
namespace App\Models {
    class Stich {
        public function findById(int $id): array {return ['id'=>$id,'id_anlass'=>2];}
        public function hasAnzeigeId(int $id,?string $number,int $except=0): bool {return false;}
        public function findByAnlassId(int $id): array {return [];}
    }
    class Auszeichnungslimitten {
        public static int $deleted=0;
        public function getById(int $id): array {return ['id'=>$id,'stich_id'=>2];}
        public function delete(int $id): void {self::$deleted++;}
        public function findByAnlassId(int $id): array {return [];}
    }
    class Gaben {public function getAll(): array {return [];}}
    class Standblatt {}
    class Schussdaten {}
}
namespace {
    require dirname(__DIR__,2).'/app/bootstrap.php';
    session_save_path(sys_get_temp_dir());
    $controller=new App\Controllers\AnlassController(new App\Services\AuthService(),new App\Services\AnlassService(),new App\Models\Gaben(),new App\Models\Stich(),new App\Models\Auszeichnungslimitten(),new App\Services\RanglistenService(new App\Models\Stich(),new App\Models\Standblatt(),new App\Models\Schussdaten()),new App\Services\KassenService(new App\Models\Standblatt(),new App\Models\Gaben()));
    try {$controller->deleteRegel(['id'=>1,'regelId'=>2]);throw new RuntimeException('Wrong-context delete accepted');}catch(OutOfBoundsException){}
    if(App\Models\Auszeichnungslimitten::$deleted!==0) throw new RuntimeException('Deleted another event rule');
    try {$controller->deleteRegel(['id'=>2,'regelId'=>2]);}catch(LogicException $e){if(!str_starts_with($e->getMessage(),'redirect:')) throw $e;}
    if(App\Models\Auszeichnungslimitten::$deleted!==1) throw new RuntimeException('Valid delete not executed');
    $_POST=['name'=>'Eingegebener Name','preis'=>'-10','anzahl_schuss'=>'5'];
    set_error_handler(static function(int $level,string $message): bool {throw new ErrorException($message);});
    ob_start();$controller->storeStich(['id'=>1]);$html=ob_get_clean();
    if(http_response_code()!==422 || !str_contains($html,'Eingegebener Name') || !str_contains($html,'-10')) throw new RuntimeException('Invalid input not retained');
    restore_error_handler();App\Core\Session::destroy();
    echo "Configuration checks passed: event-scoped deletion, validation and retained form values.\n";
}
