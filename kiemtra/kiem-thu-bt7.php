<?php
declare(strict_types=1);
// CLI: chỉ tạo/ghi/xóa CSDL thử ngẫu nhiên, router tạm chỉ lắng nghe loopback.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../inc/db.php';
$root = dirname(__DIR__);
$name = 'edugpa_bt7_' . bin2hex(random_bytes(6));
if (!preg_match('/\Aedugpa_bt7_[a-f0-9]{12}\z/D', $name)) throw new RuntimeException('Sai CSDL thử.');
$p = db(); $created = false; $server = null; $router = null; $log = null;
$results = [];
function check(bool $ok, string $case, array $evidence = []): void {
    global $results;
    $results[] = ['case'=>$case, 'pass'=>$ok] + $evidence;
    echo ($ok ? 'PASS ' : 'FAIL ') . $case . PHP_EOL;
    if (!$ok) throw new RuntimeException($case);
}
try {
    $p->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $schema = file_get_contents($root.'/database/schema.sql');
    $schema = preg_replace('/^DROP DATABASE.*;\R|^CREATE DATABASE.*;\R/m', '', $schema);
    $schema = str_replace('ltweb_nhom03', $name, $schema);
    $p->exec($schema);
    $p->exec(str_replace('ltweb_nhom03', $name, file_get_contents($root.'/database/seed.sql')));
    $payload = '<b>BT7</b> " \' &';
    $st=$p->prepare('INSERT INTO tv_tra_cuu_mang (mssv,muc_tieu,dia_chi_ip,loai_ip) VALUES (?,?,?,?)');
    $st->execute(['3120224064',$payload,'127.0.0.1','IPv4']);
    $ownId=(int)$p->lastInsertId();
    $st->execute(['3120224042','other.invalid','127.0.0.2','IPv4']);
    $otherId=(int)$p->lastInsertId();
    $user=$p->prepare('INSERT INTO nguoi_dung (ten_dang_nhap,email,mat_khau,ho_ten,vai_tro) VALUES (?,?,?,?,?)');
    $user->execute(['bt7_guest','bt7@example.invalid',password_hash('Test-only-123',PASSWORD_DEFAULT),$payload,'khach_hang']);
    $uid=(int)$p->lastInsertId();
    $router=tempnam(sys_get_temp_dir(),'bt7_router_');
    $log=tempnam(sys_get_temp_dir(),'bt7_log_');
    $secret=bin2hex(random_bytes(16));
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);
    if(!$socket) throw new RuntimeException($errstr);
    $port=(int)substr(strrchr(stream_socket_get_name($socket,false),':'),1); fclose($socket);
    file_put_contents($router,'<?php require_once '.var_export($root.'/inc/db.php',true).'; db()->exec('.var_export('USE `'.$name.'`',true).');'
        .'if(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH)==="/'.$secret.'"){session_start(); $_SESSION=["uid"=>'.$uid.',"chu_gio_hang"=>'.$uid.']; echo "fixture"; return true;} return false;');
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$root,$router],[0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes);
    if(!is_resource($server)) throw new RuntimeException('Không mở server.');
    fclose($pipes[0]);
    for($i=0;$i<50;$i++){ $s=@fsockopen('127.0.0.1',$port); if($s){fclose($s);break;} usleep(100000); }
    $client=curl_init(); curl_setopt($client,CURLOPT_COOKIEFILE,'');
    $http=function(string $path,string $method='GET',array|string $body=[],array $headers=[]) use($client,$port): array {
        curl_setopt_array($client,[CURLOPT_URL=>'http://127.0.0.1:'.$port.'/'.$path,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_POSTFIELDS=>null,
            CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10]);
        if($method==='POST') curl_setopt($client,CURLOPT_POSTFIELDS,is_array($body)?http_build_query($body):$body);
        $text=curl_exec($client);
        if($text===false) throw new RuntimeException(curl_error($client));
        $code=curl_getinfo($client,CURLINFO_RESPONSE_CODE);
        if(str_contains($text,'Warning:')||str_contains($text,'Fatal error:')) throw new RuntimeException('PHP diagnostic: '.$path);
        return ['status'=>$code,'body'=>$text];
    };
    $page='thanhvien/3120224064_hop/gioithieu.php';
    $counts=fn()=>[$p->query('SELECT COUNT(*) FROM tv_tra_cuu_mang')->fetchColumn(),$p->query('SELECT COUNT(*) FROM tv_binh_chon')->fetchColumn()];
    $before=$counts();
    $r=$http($page);
    preg_match('/name="_csrf" value="([a-f0-9]{64})"/',$r['body'],$m); $token=$m[1]??'';
    check($r['status']===200 && strlen($token)===64,'GET trang và token');
    $escaped=htmlspecialchars($payload,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    check(str_contains($r['body'],$escaped)&&!str_contains($r['body'],$payload),'B1 Stored XSS lịch sử hiện nguyên văn');
    check(!preg_match('/\sstyle=|<script(?![^>]*\bsrc=)/i',$r['body']),'B2 Không script/style nội tuyến trong HTML trang Hợp');
    foreach(['tra_cuu_mang','binh_chon','kiem_tra_pass'] as $action){
        $r=$http($page,'POST',['hanh_dong'=>$action,'target'=>'127.0.0.1','tuy_chon'=>'huu_ich','password'=>'x']);
        check($r['status']===403 && $counts()===$before,'B3 CSRF thiếu '.$action,['status'=>$r['status'],'db_unchanged'=>true]);
    }
    foreach(['sai', ['array']] as $bad){
        $r=$http($page,'POST',['_csrf'=>$bad,'hanh_dong'=>'tra_cuu_mang','target'=>'127.0.0.1']);
        check($r['status']===403 && $counts()===$before,'B4 CSRF sai hoặc mảng',['status'=>$r['status'],'db_unchanged'=>true]);
    }
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'tra_cuu_mang','target'=>$payload]);
    check($r['status']===200 && str_contains($r['body'],$escaped)&&$counts()===$before,'B5 Reflected XSS đầu vào bị từ chối và escape');
    $r=$http($page.'?trang%5B%5D=1');
    check($r['status']===200,'B6 GET trang dạng mảng',['status'=>$r['status']]);
    foreach(['target','password'] as $field){
        $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>$field==='target'?'tra_cuu_mang':'kiem_tra_pass',$field=>['x'],'ten'=>['x']]);
        check($r['status']===200 && $counts()===$before,'B7 POST mảng '.$field,['status'=>$r['status']]);
    }
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'xoa','id'=>$ownId]);
    check($r['status']===400 && $counts()===$before,'B8 Khách giả mạo xóa bị từ chối',['status'=>$r['status'],'db_unchanged'=>true]);
    $http($secret);
    $r=$http($page); preg_match('/name="_csrf" value="([a-f0-9]{64})"/',$r['body'],$m); $token=$m[1];
    check(str_contains($r['body'],$escaped),'B9 Tên tài khoản chứa HTML được escape');
    foreach([$ownId,$otherId] as $id){
        $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'xoa','id'=>$id,'mssv'=>'3120224042']);
        check($r['status']===400 && $counts()===$before,'B10 Tài khoản khác giả mạo xóa id '.$id,['status'=>$r['status'],'db_unchanged'=>true]);
    }
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>['xoa'],'id'=>[$otherId]]);
    check($r['status']===400,'B11 Hành động và id dạng mảng',['status'=>$r['status']]);
    $r=$http($page.'?hanh_dong=tra_cuu_mang&target=127.0.0.1');
    check($r['status']===200 && $counts()===$before,'B12 GET không ghi lịch sử',['db_unchanged'=>true]);
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'tra_cuu_mang','target'=>'127.0.0.1','mssv'=>'3120224042']);
    check($r['status']===303 && (int)$counts()[0]===(int)$before[0]+1,'B13 Tra cứu hợp lệ hoạt động',['status'=>$r['status']]);
    check($p->query('SELECT mssv FROM tv_tra_cuu_mang ORDER BY id DESC LIMIT 1')->fetchColumn()==='3120224064','B14 Không tin MSSV từ form');
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'binh_chon','tuy_chon'=>'huu_ich']);
    check($r['status']===303 && (int)$counts()[1]===(int)$before[1]+1,'B15 Bình chọn hợp lệ');
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'binh_chon','tuy_chon'=>'huu_ich']);
    check($r['status']===303 && (int)$counts()[1]===(int)$before[1]+1,'B16 Bình chọn trùng không thêm dòng');
    foreach(['dang-nhap.php','dang-ky.php','quen-mat-khau.php','dat-lai-mat-khau.php','lien-he.php','gio-hang.php','dat-hang.php','don-hang.php','quan-tri-mon-hoc.php','xoa-mon-hoc.php','dang-xuat.php',
        'thanhvien/3120224034_Duy/gioithieu.php','thanhvien/3120224042_hai/gioithieu.php','thanhvien/3120224159_anhtruc/gioithieu.php','thanhvien/3120224174_uyen/gioithieu.php'] as $path){
        $r=$http($path,'POST',[]);
        check($r['status']===403,'A CSRF toàn nhóm '.$path,['status'=>$r['status']]);
    }
    $r=$http('api/gio-hang.php','POST','{"id":1,"sl":1}',['Content-Type: application/json']);
    check($r['status']===403 && isset(json_decode($r['body'],true)['loi']),'A API thiếu CSRF trả JSON 403');
    $r=$http('api/gio-hang.php','POST','{"id":1,"sl":1}',['Content-Type: application/json','X-CSRF-Token: '.$token]);
    check($r['status']===200,'A API header đúng thêm giỏ');
    $r=$http('api/gio-hang.php','POST','{id:1}',['Content-Type: application/json','X-CSRF-Token: '.$token]);
    check($r['status']===400,'A API JSON sai với token đúng trả 400');
    $r=$http('gio-hang.php','POST',['_csrf'=>$token,'hanh_dong'=>'them','id'=>1,'so_luong'=>1]);
    check($r['status']===302,'A Form giỏ không JS hoạt động');
    $views=fn()=>(int)$p->query('SELECT COALESCE(SUM(so_luot),0) FROM tv_luot_xem')->fetchColumn();
    $v=$views();
    $r=$http('thanhvien/3120224042_hai/gioithieu.php');
    check($r['status']===200 && $views()===$v,'A GET Hải không ghi lượt xem');
    $r=$http('thanhvien/3120224042_hai/gioithieu.php','POST',['hanh_dong'=>'ghi_luot_xem'],['X-CSRF-Token: '.$token]);
    check($r['status']===204 && $views()===$v+1,'A POST Hải có CSRF ghi lượt xem');
    $r=$http('dang-xuat.php'); check($r['status']===405,'A Đăng xuất GET trả 405');
    $r=$http('dang-xuat.php','POST',['_csrf'=>$token]); check($r['status']===303,'A Đăng xuất POST hợp lệ');
    $r=$http($page,'POST',['_csrf'=>$token,'hanh_dong'=>'tra_cuu_mang','target'=>'127.0.0.1']);
    check($r['status']===403,'A Token phiên cũ sau đăng xuất bị từ chối');
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if($router && is_file($router)) unlink($router);
    if($log && is_file($log)) unlink($log);
    if($created) $p->exec('DROP DATABASE `'.$name.'`');
    file_put_contents(__DIR__.'/bt7-ket-qua.json',json_encode(['database'=>'CSDL thử ngẫu nhiên đã xóa','php'=>PHP_VERSION,'cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
