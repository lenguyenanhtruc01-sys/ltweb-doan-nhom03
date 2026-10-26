<?php
declare(strict_types=1);
// Chạy CLI. Chỉ ghi/xóa CSDL edugpa_pc_<12 hex> do chính lượt chạy tạo.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db.php';
use App\Data\KhoKeHoach;
use App\Data\KhoMonHoc;
use App\Data\KhoNguoiDung;
use App\Data\KhoTrangThaiNguoiDung;

function out(string $label, mixed $value): void {
    echo $label, ': ', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
}
function useTest(PDO $p, string $name): void {
    if (!preg_match('/\Aedugpa_pc_[a-f0-9]{12}\z/D', $name)) throw new RuntimeException('Tên CSDL thử sai.');
    $p->exec('USE `' . $name . '`');
}
// Tiến trình riêng để cache nguoiDung() và session không trộn giữa các request.
if (($argv[1] ?? '') === '--page') {
    $p = db(); useTest($p, $argv[2]);
    session_start(); $_SESSION = ['ke_hoach_dang_ky'=>[1=>1,2=>1,3=>1]];
    if ((int)$argv[4] > 0) { $_SESSION['uid']=(int)$argv[4]; $_SESSION['chu_gio_hang']=(int)$argv[4]; }
    $_COOKIE=['da_xem'=>'1,2,3,4']; $_SERVER['REQUEST_METHOD']='GET'; $_GET=[];
    $before=(int)$p->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch(PDO::FETCH_NUM)[1];
    ob_start(); require __DIR__ . '/../' . ( $argv[3] === 'index' ? 'index.php' : 'gio-hang.php'); ob_end_clean();
    $after=(int)$p->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch(PDO::FETCH_NUM)[1];
    out('SELECT '.$argv[3].' uid='.$argv[4],$after-$before); exit;
}
if (($argv[1] ?? '') === '--worker') {
    $p=db(); useTest($p,$argv[2]);
    try { (new KhoKeHoach($p))->taoKeHoach((int)$argv[3],[1=>1]); echo 'THANH_CONG'; }
    catch (App\Data\MonKhongDuChoException) { echo 'THIEU_CHO'; }
    exit;
}
// Biến thể thực nghiệm: cùng mã nghiệp vụ, tắt ba thao tác giao dịch.
class KhongGiaoDichPDO extends PDO {
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollBack(): bool { return true; }
    public function inTransaction(): bool { return false; }
}
$p=db(); $name='edugpa_pc_'.bin2hex(random_bytes(6)); $created=false;
$children=[]; $server=null; $router=null;
try {
    $p->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $created=true;
    useTest($p,$name);
    // Sao chép cấu trúc local đã nâng cấp; không sao chép tài khoản cá nhân.
    foreach (['danh_muc','mon_hoc','nguoi_dung','ke_hoach_hoc_tap','chi_tiet_ke_hoach','gio_hang_nguoi_dung','mon_yeu_thich'] as $t) {
        $p->exec('CREATE TABLE `'.$t.'` LIKE ltweb_nhom03.`'.$t.'`');
    }
    foreach (['danh_muc','mon_hoc'] as $t) $p->exec('INSERT INTO `'.$t.'` SELECT * FROM ltweb_nhom03.`'.$t.'`');
    $users=new KhoNguoiDung($p); $password='PhanC_2026!';
    $u1=$users->dangKy('pc_a','pc_a@example.invalid',password_hash($password,PASSWORD_DEFAULT),'Thử A');
    $u2=$users->dangKy('pc_b','pc_b@example.invalid',password_hash($password,PASSWORD_DEFAULT),'Thử B');
    out('MOI_TRUONG',['commit'=>getenv('EDUGPA_TEST_REVISION') ?: 'working-tree','database'=>$name,'server'=>$p->query('SELECT VERSION()')->fetchColumn(),'courses'=>$p->query('SELECT COUNT(*) FROM mon_hoc')->fetchColumn(),'schema'=>'LIKE local đã nâng cấp, dữ liệu môn/danh mục local']);
    $q="Levi's"; $bad="SELECT * FROM mon_hoc WHERE (ten_mon LIKE '%$q%' OR ma_mon LIKE '%$q%') ORDER BY id DESC LIMIT 6";
    out('SQL_GHEP',$bad);
    try { $p->query($bad); } catch (PDOException $e) { out('LOI_GHEP',[$e->getCode(),$e->errorInfo[1],$e->getMessage()]); }
    $safe=(new KhoMonHoc($p))->trang(['q'=>$q,'dm'=>null,'tcToiDa'=>null],'moi',6,0); out('PREPARED_LEVIS',count($safe));
    foreach (['gia','so_tin_chi'] as $column) {
        $s=$p->prepare('SELECT id,so_tin_chi FROM mon_hoc ORDER BY ? LIMIT 6'); $s->execute([$column]); out('ORDER_BY_THAM_SO_'.$column,$s->fetchAll());
    }
    out('ORDER_BY_COT',$p->query('SELECT id,so_tin_chi FROM mon_hoc ORDER BY so_tin_chi ASC,id LIMIT 6')->fetchAll());
    $loc=['q'=>'','dm'=>null,'tcToiDa'=>null]; $k=new KhoMonHoc($p);
    out('SX_BAT_KY_BANG_MOI',array_map(fn($m)=>$m->id,$k->trang($loc,'bat-ky',6,0))===array_map(fn($m)=>$m->id,$k->trang($loc,'moi',6,0)));
    $selectCount=static fn():int=>(int)$p->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch(PDO::FETCH_NUM)[1];
    foreach (['da_xem'=>[1,2,3,4],'gio'=>[1,2,3]] as $label=>$ids) {
        $before=$selectCount(); foreach($ids as $id) $k->timTheoId($id); $old=$selectCount()-$before;
        $before=$selectCount(); $k->theoNhieuId($ids); $new=$selectCount()-$before;
        out('SO_SANH_VONG_LAP_VA_IN_'.$label,['vong_lap_thuc_nghiem'=>$old,'IN_hien_tai'=>$new]);
    }
    $queries=['loc'=>'SELECT * FROM mon_hoc WHERE danh_muc_id=1 ORDER BY so_tin_chi ASC,id LIMIT 6 OFFSET 0','tim'=>"SELECT * FROM mon_hoc WHERE (ten_mon LIKE '%Web%' OR ma_mon LIKE '%Web%') ORDER BY id DESC LIMIT 6 OFFSET 0"];
    foreach ($queries as $label=>$sql) out('EXPLAIN_TRUOC_'.$label,$p->query('EXPLAIN '.$sql)->fetchAll());
    $p->exec('CREATE INDEX idx_pc_dm_tc ON mon_hoc(danh_muc_id,so_tin_chi)');
    foreach ($queries as $label=>$sql) out('EXPLAIN_SAU_'.$label,$p->query('EXPLAIN '.$sql)->fetchAll());
    $p->exec('DROP INDEX idx_pc_dm_tc ON mon_hoc');
    $dsn='mysql:host='.(getenv('DB_HOST')?:'127.0.0.1').';port='.(getenv('DB_PORT')?:'3306').';dbname='.$name.';charset=utf8mb4';
    $broken=new KhongGiaoDichPDO($dsn,getenv('DB_USER')===false?'root':getenv('DB_USER'),getenv('DB_PASS')===false?'':getenv('DB_PASS'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    foreach (['KHONG_TRANSACTION'=>$broken,'CO_TRANSACTION'=>$p] as $label=>$conn) {
        $p->exec('DELETE FROM chi_tiet_ke_hoach'); $p->exec('DELETE FROM ke_hoach_hoc_tap');
        $p->exec('UPDATE mon_hoc SET so_cho=2 WHERE id=1'); $p->exec('UPDATE mon_hoc SET so_cho=0 WHERE id=2');
        try { (new KhoKeHoach($conn))->taoKeHoach($u1,[1=>1,2=>1]); } catch (App\Data\MonKhongDuChoException $e) { out($label.'_loi',$e->getMessage()); }
        out($label,['ke_hoach'=>$p->query('SELECT COUNT(*) FROM ke_hoach_hoc_tap')->fetchColumn(),'chi_tiet'=>$p->query('SELECT COUNT(*) FROM chi_tiet_ke_hoach')->fetchColumn(),'cho'=>$p->query('SELECT id,so_cho FROM mon_hoc WHERE id IN(1,2) ORDER BY id')->fetchAll()]);
    }
    $p->exec('UPDATE mon_hoc SET so_cho=1 WHERE id=1');
    foreach ([$u1,$u2] as $uid) {
        $pipes=[]; $proc=proc_open([PHP_BINARY,__FILE__,'--worker',$name,(string)$uid],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if (!is_resource($proc)) throw new RuntimeException('Không mở được worker.'); fclose($pipes[0]); $children[]=[$proc,$pipes];
    }
    $results=[]; foreach ($children as [$proc,$pipes]) { $results[]=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); if(proc_close($proc)!==0||$err!=='') throw new RuntimeException($err); } $children=[];
    out('HAI_KHACH',$results); out('SAU_TRANH_CHO',['ke_hoach'=>$p->query('SELECT COUNT(*) FROM ke_hoach_hoc_tap')->fetchColumn(),'so_cho'=>$p->query('SELECT so_cho FROM mon_hoc WHERE id=1')->fetchColumn()]);
    $row=$p->query('SELECT id,nguoi_dung_id FROM ke_hoach_hoc_tap LIMIT 1')->fetch();
    $owner=(int)$row['nguoi_dung_id']; $other=$owner===$u1?$u2:$u1; $plan=(int)$row['id'];
    $kh=new KhoKeHoach($p); out('IDOR_DOC',['chu'=>$kh->chiTiet($plan,$owner,false)!==null,'nguoi_khac'=>$kh->chiTiet($plan,$other,false),'quan_tri'=>$kh->chiTiet($plan,$other,true)!==null]);
    $snapshot=$kh->chiTiet($plan,$owner,false)['chi_tiet']; $p->exec("UPDATE mon_hoc SET so_tin_chi=19,ten_mon='Da doi sau dang ky' WHERE id=1");
    out('SNAPSHOT_GIU_NGUYEN',$snapshot===$kh->chiTiet($plan,$owner,false)['chi_tiet']);
    $state=new KhoTrangThaiNguoiDung($p); $state->themMon($u1,1,1); $state->themMon($u2,2,1);
    out('IDOR_GIO',['xoa_mon_cua_B_bang_uid_A'=>$state->xoaMon($u1,2),'gio_B'=>$state->gioHang($u2)]);
    $hashes=$p->query('SELECT ten_dang_nhap,mat_khau FROM nguoi_dung ORDER BY id')->fetchAll(); out('HAI_MAT_KHAU',$hashes);
    out('PASSWORD_VERIFY',array_map(fn($r)=>password_verify($password,$r['mat_khau']),$hashes));
    // HTTP thật để thử URL, ô ẩn và JSON. Router chỉ tồn tại trong thư mục tạm.
    $router=tempnam(sys_get_temp_dir(),'edugpa_pc_');
    $secret=bin2hex(random_bytes(12)); $root=dirname(__DIR__);
    $portSocket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);
    if($portSocket===false) throw new RuntimeException($errstr);
    $port=(int)substr(strrchr(stream_socket_get_name($portSocket,false),':'),1); fclose($portSocket);
    $routerCode='<?php require_once '.var_export($root.'/inc/db.php',true).'; db()->exec('.var_export('USE `'.$name.'`',true).');'
        .'if(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH)==="/'.$secret.'"){session_start(); $_SESSION=["uid"=>(int)$_GET["uid"],"chu_gio_hang"=>(int)$_GET["uid"],"csrf_gio_hang"=>"pc-token","_csrf_token"=>"pc-form"]; echo session_id(); return true;} return false;';
    file_put_contents($router,$routerCode);
    $serverLog=tempnam(sys_get_temp_dir(),'edugpa_pc_log_');
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$root,$router],[0=>['pipe','r'],1=>['file',$serverLog,'a'],2=>['file',$serverLog,'a']],$serverPipes);
    if(!is_resource($server)) throw new RuntimeException('Không mở server HTTP.'); fclose($serverPipes[0]);
    $ready=false; for($i=0;$i<30;$i++) { $socket=@fsockopen('127.0.0.1',$port,$errno,$errstr,0.1); if($socket){fclose($socket);$ready=true;break;} usleep(100000); }
    if(!$ready) throw new RuntimeException('Server HTTP chưa sẵn sàng.');
    $http=static function(string $path,string $method='GET',string $body='',string $cookie='',bool $json=false) use($port): array {
        $headers=['Cookie: PHPSESSID='.$cookie,'X-CSRF-Token: pc-token','Content-Type: '.($json?'application/json':'application/x-www-form-urlencoded')];
        $context=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$body,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>5]]);
        $result=file_get_contents('http://127.0.0.1:'.$port.'/'.$path,false,$context);
        return ['status'=>$http_response_header[0]??'','body'=>$result];
    };
    $sid=$http($secret.'?uid='.$other)['body'];
    foreach (['don-hang.php?id='.$plan,'quan-tri.php','quan-tri-mon-hoc.php?sua=1'] as $path) {
        $r=$http($path,'GET','',$sid); out('HTTP_'.$path,$r['status']);
    }
    foreach (['don-hang.php'=>'_csrf=pc-form&id='.$plan.'&trang_thai=da_duyet','xoa-mon-hoc.php'=>'_csrf=pc-form&id=1'] as $path=>$body) {
        $r=$http($path,'POST',$body,$sid); out('HTTP_POST_'.$path,$r['status']);
    }
    $r=$http('api/yeu-thich.php','POST',json_encode(['id'=>1,'chon'=>true,'uid'=>$owner,'nguoi_dung_id'=>$owner]),$sid,true);
    out('HTTP_JSON_GIA_UID',['status'=>$r['status'],'body'=>$r['body'],'yeu_thich_nguoi_gui'=>$state->yeuThich($other),'yeu_thich_nguoi_bi_gia'=>$state->yeuThich($owner)]);
    $appLog=$root.'/logs/php-error.log'; clearstatcache(true,$appLog); $logOffset=is_file($appLog)?filesize($appLog):0;
    // Đặt lại chỗ sau thí nghiệm rollback để đây thực sự là request hợp lệ.
    $p->exec('UPDATE mon_hoc SET so_cho=2 WHERE id=2');
    $r=$http('api/gio-hang.php','POST','{"id":2,"sl":1}',$sid,true); out('HTTP_THEM_GIO_MODEL_HIEN_TAI',$r);
    if (!str_contains($r['status'], ' 200 ')) throw new RuntimeException('API thêm giỏ hợp lệ chưa trả 200.');
    $logText=is_file($appLog)?substr((string)file_get_contents($appLog),(int)$logOffset):'';
    out('NGUYEN_NHAN_API_500',str_contains($logText,'Undefined property: App\\Models\\MonHoc::$soCho'));
    $r=$http('api/yeu-thich.php'); out('HTTP_YEU_THICH_KHACH',$r);
    proc_terminate($server); proc_close($server); $server=null;
    unlink($router); $router=null; unlink($serverLog);
    $p->exec('DELETE FROM gio_hang_nguoi_dung');
    foreach ([1,2,3] as $id) $state->themMon($u1,$id,1);
    foreach (['index','gio-hang'] as $page) foreach ([0,$u1] as $uid) {
        $pipes=[]; $proc=proc_open([PHP_BINARY,__FILE__,'--page',$name,$page,(string)$uid],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($proc)) throw new RuntimeException('Không mở được page worker.'); fclose($pipes[0]);
        echo stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $code=proc_close($proc); if($code!==0||$err!=='') out('PAGE_WARNING',$err);
    }
} finally {
    if(is_resource($server)) {proc_terminate($server);proc_close($server);}
    if($router!==null && is_file($router)) unlink($router);
    foreach($children as [$proc,$pipes]) { if(is_resource($proc)) {proc_terminate($proc); proc_close($proc);} }
    if ($created) { useTest($p,$name); $p->exec('DROP DATABASE `'.$name.'`'); out('DA_DON_CSDL',$name); }
}
