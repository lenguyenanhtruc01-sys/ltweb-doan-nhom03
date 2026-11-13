<?php
declare(strict_types=1);
// DB_USER/DB_PASS dùng để tạo CSDL thử trên MySQL cục bộ (mặc định root của XAMPP).
// Website chạy bằng tài khoản chỉ có quyền DML trên CSDL thử. Kết quả ghi vào thư mục tạm.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';
$root = dirname(__DIR__);
$name = 'edugpa_bt7_' . bin2hex(random_bytes(6));
$p = new PDO(
    'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$dbPassword = bin2hex(random_bytes(24));
$dbAccount = $p->quote($name) . "@'127.0.0.1'";
$created = false;
$createdAccount = false;
$server = null;
$serverLoi = null;
$log = tempnam(sys_get_temp_dir(), 'edugpa-bt7-');
if ($log === false) throw new RuntimeException('Không tạo được tệp log của máy chủ kiểm thử.');
$thuMucPhien = $log . '-phien';
$webCauHinh = $thuMucPhien . '/web';
$tepWebCauHinh = ['router.php', '500.php', 'index.php', 'api/san-pham.php',
    'inc/config.php', 'inc/db.php', 'inc/ham.php', 'inc/csrf.php', 'inc/xac-thuc.php', 'inc/api.php'];
$results = [];
function check(bool $ok, string $case, array $evidence = []): void {
    global $results;
    $results[] = ['case'=>$case, 'pass'=>$ok] + $evidence;
    echo ($ok ? 'PASS ' : 'FAIL ') . $case . PHP_EOL;
    if (!$ok) throw new RuntimeException($case);
}
function moMayChu(string $root, string $log, string $thuMucPhien, array $cauHinh): array
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$socket) throw new RuntimeException($errstr);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $server = proc_open(
        [PHP_BINARY, '-d', 'variables_order=EGPCS', '-d', 'session.save_path=' . $thuMucPhien,
         '-d', 'session.serialize_handler=php_serialize', '-S', $address, '-t', $root, $root . '/router.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
        $pipes, $root, $cauHinh
    );
    if (!is_resource($server)) throw new RuntimeException('Không mở server.');
    fclose($pipes[0]);
    $url = 'http://' . $address;
    $ready = '(' . $url . ') started';
    for ($i = 0; $i < 50 && !str_contains((string) file_get_contents($log), $ready); $i++) {
        usleep(100000);
    }
    if (!proc_get_status($server)['running'] || !str_contains((string) file_get_contents($log), $ready)) {
        proc_terminate($server);
        proc_close($server);
        throw new RuntimeException('Máy chủ kiểm thử không lắng nghe tại địa chỉ đã chọn.');
    }
    return [$server, $url];
}

try {
    if (!mkdir($thuMucPhien)) throw new RuntimeException('Không tạo được thư mục phiên kiểm thử.');
    $p->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    caiDatNhom($p, $name);
    $p->exec('CREATE USER ' . $dbAccount . ' IDENTIFIED BY ' . $p->quote($dbPassword));
    $createdAccount = true;
    $p->exec('GRANT SELECT, INSERT, UPDATE, DELETE ON `' . $name . '`.* TO ' . $dbAccount);
    $payload = '<b>BT7</b> " \' &';
    $st=$p->prepare('INSERT INTO tv_tra_cuu_mang (mssv,muc_tieu,dia_chi_ip,loai_ip) VALUES (?,?,?,?)');
    $st->execute(['3120224064',$payload,'127.0.0.1','IPv4']);
    $ownId=(int)$p->lastInsertId();
    $st->execute(['3120224042','other.invalid','127.0.0.2','IPv4']);
    $otherId=(int)$p->lastInsertId();
    $user=$p->prepare('INSERT INTO nguoi_dung (ten_dang_nhap,email,mat_khau,ho_ten,vai_tro) VALUES (?,?,?,?,?)');
    $user->execute(['bt7_guest','bt7@example.invalid',password_hash('Test-only-123',PASSWORD_DEFAULT),$payload,'khach_hang']);
    $uid=(int)$p->lastInsertId();
    $cauHinh = array_replace(getenv(), [
        'DB_HOST' => '127.0.0.1', 'DB_DATABASE' => $name, 'DB_USERNAME' => $name,
        'DB_PASSWORD' => $dbPassword, 'APP_DEBUG' => 'true',
    ]);
    [$server, $baseUrl] = moMayChu($root, $log, $thuMucPhien, $cauHinh);
    $client=curl_init(); curl_setopt($client,CURLOPT_COOKIEFILE,'');
    $http=function(string $path,string $method='GET',array|string $body=[],array $headers=[]) use($client,&$baseUrl): array {
        curl_setopt_array($client,[CURLOPT_URL=>$baseUrl.'/'.$path,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_POSTFIELDS=>null,
            CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10]);
        if($method==='POST') curl_setopt($client,CURLOPT_POSTFIELDS,is_array($body)?http_build_query($body):$body);
        $text=curl_exec($client);
        if($text===false) throw new RuntimeException(curl_error($client));
        $code=curl_getinfo($client,CURLINFO_RESPONSE_CODE);
        if(str_contains($text,'Warning:')||str_contains($text,'Fatal error:')) throw new RuntimeException('PHP diagnostic: '.$path);
        return ['status'=>$code,'body'=>$text,'redirect'=>curl_getinfo($client,CURLINFO_REDIRECT_URL)];
    };
    $freshToken = function () use ($http): string {
        $response = $http('dang-nhap.php');
        if ($response['status'] !== 200 || !preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $response['body'], $match)) {
            throw new RuntimeException('Không lấy được mã CSRF từ trang đăng nhập kiểm thử.');
        }
        return $match[1];
    };
    $loginFixture = function () use ($http, $freshToken): void {
        $response = $http('dang-nhap.php', 'POST', [
            '_csrf' => $freshToken(), 'email' => 'bt7_guest', 'mat_khau' => 'Test-only-123',
        ]);
        if ($response['status'] !== 303) {
            throw new RuntimeException('Tài khoản kiểm thử không đăng nhập được (HTTP ' . $response['status'] . ').');
        }
    };
    $layMaPhien = static function () use ($client): string {
        foreach (curl_getinfo($client, CURLINFO_COOKIELIST) as $dong) {
            if (preg_match('/\tPHPSESSID\t([a-zA-Z0-9,-]+)$/', $dong, $m)) return $m[1];
        }
        throw new RuntimeException('Không nhận được cookie phiên kiểm thử.');
    };
    $layGhiNho = static fn(): array => $p->query('SELECT selector, token_hash FROM phien_ghi_nho ORDER BY selector')->fetchAll();
    $layGio = static fn(): array => (new \App\Data\KhoTrangThaiNguoiDung($p))->gioHang($uid);
    $r = $http('dang-nhap.php', 'POST', [
        '_csrf' => $freshToken(), 'email' => 'bt7_guest', 'mat_khau' => 'Test-only-123', 'ghi_nho' => '1',
    ]);
    check($r['status'] === 303 && count($layGhiNho()) === 1, 'Bảng 3: tạo cookie ghi nhớ qua POST đăng nhập');
    foreach (curl_getinfo($client, CURLINFO_COOKIELIST) as $dong) {
        if (str_contains($dong, "\tedugpa_ghi_nho\t")) $cookieGhiNho = $dong;
    }
    if (!isset($cookieGhiNho)) throw new RuntimeException('Không nhận được cookie ghi nhớ.');
    curl_setopt($client, CURLOPT_COOKIELIST, 'ALL');
    curl_setopt($client, CURLOPT_COOKIELIST, $cookieGhiNho);
    $token = $freshToken();
    $mon = (int) $p->query('SELECT id FROM mon_hoc WHERE so_cho >= 1 ORDER BY id LIMIT 1')->fetchColumn();
    $http('gio-hang.php', 'POST', ['_csrf' => $token, 'hanh_dong' => 'them', 'id' => $mon, 'so_luong' => 1]);
    $ghiNhoCu = $layGhiNho();
    $r = $http('dang-nhap.php');
    check($r['status'] === 200 && str_contains($r['body'], 'Tiếp tục bằng tài khoản đã ghi nhớ')
        && $layGhiNho() === $ghiNhoCu && $layGio() === [], 'GET đăng nhập không xoay token hoặc gộp giỏ');
    $r = $http('dang-nhap.php', 'POST', ['_csrf' => $token, 'tiep_tuc' => '1']);
    check($r['status'] === 303 && count($layGhiNho()) === 1 && $layGhiNho() !== $ghiNhoCu
        && $layGio() === [$mon => 1], 'POST đăng nhập ghi nhớ xoay token và gộp giỏ khách');
    $monKhac = (int) $p->query('SELECT id FROM mon_hoc WHERE id <> ' . $mon . ' ORDER BY id LIMIT 1')->fetchColumn();
    $tepPhien = $thuMucPhien . '/sess_' . $layMaPhien();
    $duLieuPhien = unserialize((string) file_get_contents($tepPhien), ['allowed_classes' => false]);
    $duLieuPhien['ke_hoach_dang_ky'] = [$monKhac => 1];
    file_put_contents($tepPhien, serialize($duLieuPhien));
    $r = $http('gio-hang.php');
    check($r['status'] === 200 && $layGio() === [$mon => 1], 'GET giỏ tài khoản không ghi dữ liệu giỏ từ phiên cũ');

    $token = $freshToken();
    $maPhien = $layMaPhien();
    $tepPhien = $thuMucPhien . '/sess_' . $maPhien;
    $duLieuPhien = unserialize((string) file_get_contents($tepPhien), ['allowed_classes' => false]);
    $duLieuPhien['hoat_dong_cuoi'] = time() - 1801;
    file_put_contents($tepPhien, serialize($duLieuPhien));
    $ghiNhoCu = $layGhiNho();
    $r = $http('dang-xuat.php', 'POST', ['_csrf' => $token]);
    check($r['status'] === 403, 'Ca 6: POST với phiên không hoạt động quá 30 phút trả 403');
    $r = $http('don-hang.php');
    check($r['status'] === 302 && str_ends_with($r['redirect'], '/dang-nhap.php')
        && $layMaPhien() !== $maPhien && $layGhiNho() === $ghiNhoCu,
        'Ca 6: GET trang riêng tư đổi mã phiên, yêu cầu đăng nhập và không ghi CSDL');
    $r = $http('dang-nhap.php');
    $cookies = implode("\n", curl_getinfo($client, CURLINFO_COOKIELIST));
    check($r['status'] === 200 && !str_contains($r['body'], 'Tiếp tục bằng tài khoản đã ghi nhớ')
        && !str_contains($cookies, "\tedugpa_ghi_nho\t"),
        'Ca 6: cookie ghi nhớ không thay mật khẩu sau khi hết hạn phiên');
    $loginFixture();
    check($layGhiNho() === [], 'POST đăng nhập bằng mật khẩu thu hồi cookie cũ');
    curl_setopt($client, CURLOPT_COOKIELIST, 'ALL');

    $baseUrlTot = $baseUrl;
    foreach (['inc', 'api', 'vendor', 'logs', 'storage'] as $thuMuc) mkdir($webCauHinh . '/' . $thuMuc, 0775, true);
    foreach ($tepWebCauHinh as $tep) copy($root . '/' . $tep, $webCauHinh . '/' . $tep);
    file_put_contents($webCauHinh . '/vendor/autoload.php', '<?php require_once ' . var_export($root . '/vendor/autoload.php', true) . ';');
    $cauHinhTrong = array_diff_key(getenv(), array_fill_keys(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'APP_DEBUG'], true));
    [$serverLoi, $baseUrl] = moMayChu($webCauHinh, $log, $thuMucPhien, $cauHinhTrong);
    $r = $http('index.php');
    check($r['status'] === 500 && str_contains($r['body'], '.env.example')
        && !str_contains($r['body'], 'DB_HOST'), 'Ca 9: cấu hình thiếu trả HTTP 500 có hướng dẫn');
    $r = $http('api/san-pham.php');
    check($r['status'] === 500 && json_decode($r['body'], true) === ['loi' => 'Lỗi máy chủ'],
        'Ca 9: cấu hình thiếu trả JSON 500 chung cho API');
    file_put_contents($webCauHinh . '/.env', "DB_PASSWORD=\"chuoi-mau-chua-dong\n");
    $r = $http('index.php');
    check($r['status'] === 500 && str_contains($r['body'], '.env.example')
        && !str_contains($r['body'], 'chuoi-mau-chua-dong'), 'Ca 9: .env sai cú pháp không lộ nội dung');
    if (!getenv('DB_USER') && !getenv('DB_PASS')) {
        $mau = str_replace('DB_DATABASE=ltweb_nhom03', 'DB_DATABASE=' . $name, file_get_contents($root . '/.env.example'));
        file_put_contents($webCauHinh . '/.env', $mau);
        file_put_contents($webCauHinh . '/kiem-tra-pdo.php', '<?php require "inc/config.php"; db(); echo "PDO OK";');
        $r = $http('kiem-tra-pdo.php');
        check($r['status'] === 200 && $r['body'] === 'PDO OK', 'Cài mới: mẫu root với mật khẩu rỗng chạy trên localhost');
        file_put_contents($webCauHinh . '/.env', str_replace('APP_DEBUG=true', 'APP_DEBUG=false', $mau));
        $r = $http('kiem-tra-pdo.php');
        check($r['status'] === 500 && str_contains($r['body'], 'Hệ thống đang gặp sự cố'), 'Cài mới: cấu hình production từ chối root');
    }
    proc_terminate($serverLoi);
    proc_close($serverLoi);
    unlink($webCauHinh . '/.env');
    [$serverLoi, $baseUrl] = moMayChu($webCauHinh, $log, $thuMucPhien, array_replace($cauHinh, [
        'APP_DEBUG' => 'false', 'DB_PASSWORD' => $dbPassword . '-sai',
    ]));
    $r = $http('index.php');
    check($r['status'] === 500 && str_contains($r['body'], 'Hệ thống đang gặp sự cố')
        && !str_contains($r['body'], '.env.example') && !str_contains($r['body'], $name),
        'Ca 9: lỗi kết nối ở chế độ prod trả trang 500 chung');
    $r = $http('api/san-pham.php');
    check($r['status'] === 500 && json_decode($r['body'], true) === ['loi' => 'Lỗi máy chủ'],
        'Ca 9: sai mật khẩu CSDL ở chế độ prod trả JSON 500 chung');
    $baseUrl = $baseUrlTot;

    $p->exec('CREATE TABLE kiem_tra_quyen (id INT PRIMARY KEY)');
    $pdoApp = new PDO('mysql:host=127.0.0.1;dbname=' . $name, $name, $dbPassword);
    $pdoApp->exec('INSERT INTO kiem_tra_quyen VALUES (1)');
    $pdoApp->exec('UPDATE kiem_tra_quyen SET id = 2 WHERE id = 1');
    check((int) $pdoApp->query('SELECT id FROM kiem_tra_quyen')->fetchColumn() === 2
        && $pdoApp->exec('DELETE FROM kiem_tra_quyen WHERE id = 2') === 1, 'Ca 11: tài khoản ứng dụng thực hiện đủ CRUD');
    foreach (['CREATE TABLE kiem_tra_quyen_create (id INT)', 'DROP TABLE kiem_tra_quyen'] as $sql) {
        $maLoi = null;
        try {
            $pdoApp->exec($sql);
        } catch (PDOException $e) {
            $maLoi = $e->errorInfo[1] ?? null;
        }
        check($maLoi === 1142, 'Ca 11: ' . strtok($sql, ' ') . ' bị từ chối với 1142');
    }
    unset($pdoApp);
    if (in_array('--bang-3', $argv, true)) return;

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
    $loginFixture();
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
    // Kiểm thử phản hồi BT6 trên cùng CSDL riêng.
    $loginFixture();
    $r=$http($page); preg_match('/name="_csrf" value="([a-f0-9]{64})"/',$r['body'],$m); $token=$m[1];
    $p->exec("UPDATE nguoi_dung SET vai_tro='quan_tri' WHERE id=".$uid);
    $r=$http('quan-tri-mon-hoc.php','POST',['_csrf'=>$token,'ma_mon'=>'']);
    check($r['status']===422 && preg_match('/<div[^>]*role="alert"[^>]*>\s*<ul>/',$r['body'])===1
        && preg_match('/<ul[^>]*role="alert"/',$r['body'])===0,'BT6 HTML lỗi có div alert bọc ul');
    $appLog=$root.'/logs/php-error.log';
    clearstatcache(true,$appLog); $offset=is_file($appLog)?filesize($appLog):0;
    $r=$http('quen-mat-khau.php','POST',['_csrf'=>$token,'email'=>'bt7@example.invalid']);
    check($r['status']===303,'BT6 Quên mật khẩu PRG');
    $r=$http('quen-mat-khau.php');
    preg_match('/dat-lai-mat-khau.php\?token=([a-f0-9]{64})/',$r['body'],$reset);
    $resetToken=$reset[1]??'';
    check(strlen($resetToken)===64,'BT6 Có mã demo dev để thử reset');
    $newLog=is_file($appLog)?substr((string)file_get_contents($appLog),(int)$offset):'';
    check(!str_contains($newLog,$resetToken) && !str_contains($newLog,'dat-lai-mat-khau.php?token='),'BT6 Không ghi mã reset vào log');
    foreach(['123456','1234567'] as $short){
        $r=$http('dat-lai-mat-khau.php','POST',['_csrf'=>$token,'token'=>$resetToken,'mat_khau'=>$short,'xac_nhan_mat_khau'=>$short]);
        check($r['status']===200 && str_contains($r['body'],'ít nhất 8 ký tự'),'BT6 Từ chối reset '.strlen($short).' ký tự');
    }
    $r=$http('dat-lai-mat-khau.php','POST',['_csrf'=>$token,'token'=>$resetToken,'mat_khau'=>'NewPass123','xac_nhan_mat_khau'=>'NewPass123']);
    check($r['status']===303,'BT6 Reset mật khẩu đủ độ dài');
    $token=$freshToken();
    for($i=0;$i<5;$i++){
        $r=$http('dang-nhap.php','POST',['_csrf'=>$token,'email'=>$i%2?'bt7_guest':'bt7@example.invalid','mat_khau'=>'sai']);
        check($r['status']===200,'BT6 Lần đăng nhập sai '.($i+1));
    }
    curl_setopt($client,CURLOPT_COOKIELIST,'ALL');
    $token=$freshToken();
    $r=$http('dang-nhap.php','POST',['_csrf'=>$token,'email'=>'bt7_guest','mat_khau'=>'NewPass123']);
    check($r['status']===429,'BT6 Khóa sau 5 lần sai dù đổi cookie và dùng mật khẩu đúng');
    $p->exec('UPDATE dang_nhap_sai SET thoi_gian=NOW()-INTERVAL 16 MINUTE');
    $r=$http('dang-nhap.php','POST',['_csrf'=>$token,'email'=>'bt7@example.invalid','mat_khau'=>'NewPass123']);
    check($r['status']===303 && (int)$p->query('SELECT COUNT(*) FROM dang_nhap_sai')->fetchColumn()===0,'BT6 Hết hạn đăng nhập được và xóa lần sai');
    $token=$freshToken();
    $lock=$p->prepare('INSERT INTO dang_nhap_sai (khoa,ip) VALUES (?,?)');
    for($i=0;$i<5;$i++) $lock->execute([hash('sha256','uid:'.$uid),'127.0.0.1']);
    $r=$http('dang-nhap.php','POST',['_csrf'=>$token,'email'=>'bt7_guest','mat_khau'=>'NewPass123']);
    check($r['status']===429,'Reset: tài khoản đang bị khóa');
    $unlockToken=bin2hex(random_bytes(32));
    $st=$p->prepare('INSERT INTO yeu_cau_dat_lai_mat_khau (nguoi_dung_id,token_hash,het_han) VALUES (?,?,NOW()+INTERVAL 30 MINUTE)');
    $st->execute([$uid,hash('sha256',$unlockToken)]);
    $r=$http('dat-lai-mat-khau.php','POST',['_csrf'=>$token,'token'=>$unlockToken,'mat_khau'=>'NewPass456','xac_nhan_mat_khau'=>'NewPass456']);
    check($r['status']===303,'Reset: đổi mật khẩu khi tài khoản đang khóa');
    $token=$freshToken();
    $r=$http('dang-nhap.php','POST',['_csrf'=>$token,'email'=>'bt7@example.invalid','mat_khau'=>'NewPass456']);
    check($r['status']===303,'Reset: đăng nhập ngay bằng mật khẩu mới không chờ 15 phút');
    check(!preg_match('/error_log\s*\(/',file_get_contents($root.'/quen-mat-khau.php')),'BT6 Không còn lệnh log trong luồng quên mật khẩu');
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if(is_resource($serverLoi)){proc_terminate($serverLoi);proc_close($serverLoi);}
    if (is_dir($webCauHinh)) {
        $tepWebCauHinh = array_merge($tepWebCauHinh, ['.env', 'kiem-tra-pdo.php', 'vendor/autoload.php', 'logs/php-error.log']);
        foreach ($tepWebCauHinh as $tep) {
            if (is_file($webCauHinh . '/' . $tep)) unlink($webCauHinh . '/' . $tep);
        }
        foreach (['inc', 'api', 'vendor', 'logs', 'storage'] as $thuMuc) rmdir($webCauHinh . '/' . $thuMuc);
        rmdir($webCauHinh);
    }
    if (is_dir($thuMucPhien)) {
        foreach (glob($thuMucPhien . '/sess_*') as $tepPhien) unlink($tepPhien);
        rmdir($thuMucPhien);
    }
    unlink($log);
    try {
        if ($createdAccount) $p->exec('DROP USER ' . $dbAccount);
    } finally {
        if ($created) $p->exec('DROP DATABASE `' . $name . '`');
    }
    $report = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name . '-ket-qua.json';
    $json = json_encode([
        'database' => $name, 'php' => PHP_VERSION, 'cases' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($report, $json) === false) {
        throw new RuntimeException('Không ghi được kết quả kiểm thử.');
    }
    echo 'Kết quả kiểm thử: ' . $report . PHP_EOL;
}
