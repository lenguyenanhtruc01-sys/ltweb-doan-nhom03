<?php
declare(strict_types=1);

/**
 * tests/kiem-thu-phan-b.php — kiểm thử phủ định Phần B trên trang cá nhân Duy.
 *
 * Chạy từ thư mục dự án: php tests/kiem-thu-phan-b.php
 * Bộ thử tự mở máy chủ trên 127.0.0.1 và tạo CSDL cùng tài khoản MySQL riêng.
 * Cần MySQL cục bộ trên cổng 3306. DB_USER/DB_PASS là tài khoản quản trị CLI
 * (mặc định root và mật khẩu rỗng của XAMPP).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

require_once __DIR__ . '/../database/cai-dat.php';
const TRANG = '/thanhvien/3120224034_Duy/gioithieu.php';
const MSSV_DUY = '3120224034';
const MSSV_KHAC = '3120224042';

$thuMucTam = sys_get_temp_dir();
$jarDuy = $thuMucTam . '/bt7-duy-' . bin2hex(random_bytes(4)) . '.cookie';
$jarHop = $thuMucTam . '/bt7-hop-' . bin2hex(random_bytes(4)) . '.cookie';
$ketQua = [];

function check(bool $dat, string $ten, string $chiTiet = ''): void
{
    global $ketQua;
    $ketQua[] = $dat;
    echo ($dat ? '[ĐẠT] ' : '[KHÔNG ĐẠT] ') . $ten . ($chiTiet !== '' ? ' — ' . $chiTiet : '') . PHP_EOL;
}

/** Gửi request và trả về [mã trạng thái, thân phản hồi, header]. */
function gui(string $duongDan, string $method = 'GET', array $duLieu = [], array $header = [], string $jar = '', bool $theoRedirect = false): array
{
    global $gocKiemThu;
    $ch = curl_init($gocKiemThu . $duongDan);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $theoRedirect,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
    ]);
    if ($jar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($duLieu));
    }
    if ($header !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException('curl: ' . curl_error($ch));
    }
    $maTrangThai = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $kichThuocHeader = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$maTrangThai, substr($raw, $kichThuocHeader), substr($raw, 0, $kichThuocHeader)];
}

function maCsrf(string $html): string
{
    if (preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/name="csrf-token" content="([a-f0-9]{64})"/', $html, $m)) {
        return $m[1];
    }
    throw new RuntimeException('Không tìm thấy mã CSRF trong trang.');
}

function dangNhap(string $jar, string $taiKhoan): string
{
    [$ma, $html] = gui('/dang-nhap.php', 'GET', [], [], $jar);
    if ($ma !== 200) {
        throw new RuntimeException('Trang đăng nhập trả HTTP ' . $ma);
    }
    $token = maCsrf($html);
    [$ma] = gui('/dang-nhap.php', 'POST', [
        'email' => $taiKhoan,
        'mat_khau' => 'matkhau123',
        '_csrf' => $token,
    ], [], $jar);
    if ($ma !== 303) {
        throw new RuntimeException('Đăng nhập ' . $taiKhoan . ' trả ' . $ma);
    }
    [, $trang] = gui(TRANG, 'GET', [], [], $jar);
    return maCsrf($trang);
}

function demMon(): int
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM tv_mon_an')->fetchColumn();
}

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$tenCsdl = 'edugpa_duy_' . bin2hex(random_bytes(6));
$matKhau = bin2hex(random_bytes(24));
$taiKhoan = $pdo->quote($tenCsdl) . "@'127.0.0.1'";
$daTaoCsdl = false;
$daTaoTaiKhoan = false;
$mayChu = null;
$logMayChu = tempnam(sys_get_temp_dir(), 'edugpa-duy-');
if ($logMayChu === false) {
    throw new RuntimeException('Không tạo được tệp log của máy chủ kiểm thử.');
}

try {
    $pdo->exec('CREATE DATABASE `' . $tenCsdl . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $daTaoCsdl = true;
    caiDatNhom($pdo, $tenCsdl);
    $pdo->exec('CREATE USER ' . $taiKhoan . ' IDENTIFIED BY ' . $pdo->quote($matKhau));
    $daTaoTaiKhoan = true;
    $pdo->exec('GRANT SELECT, INSERT, UPDATE, DELETE ON `' . $tenCsdl . '`.* TO ' . $taiKhoan);

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    if ($socket === false) {
        throw new RuntimeException('Không lấy được cổng kiểm thử.');
    }
    $diaChi = stream_socket_get_name($socket, false);
    fclose($socket);
    $gocKiemThu = 'http://' . $diaChi;
    $thuMucDuAn = dirname(__DIR__);
    $mayChu = proc_open(
        [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', $diaChi, '-t', $thuMucDuAn, $thuMucDuAn . '/router.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $logMayChu, 'a'], 2 => ['file', $logMayChu, 'a']],
        $ong,
        $thuMucDuAn,
        array_replace(getenv(), [
            'DB_HOST' => '127.0.0.1',
            'DB_DATABASE' => $tenCsdl,
            'DB_USERNAME' => $tenCsdl,
            'DB_PASSWORD' => $matKhau,
            'APP_DEBUG' => 'false',
        ])
    );
    if (!is_resource($mayChu)) {
        throw new RuntimeException('Không khởi động được máy chủ kiểm thử.');
    }
    fclose($ong[0]);
    for ($i = 0; $i < 50 && !str_contains((string) file_get_contents($logMayChu), '(' . $gocKiemThu . ') started'); $i++) {
        usleep(100000);
    }
    if (!proc_get_status($mayChu)['running']
        || !str_contains((string) file_get_contents($logMayChu), '(' . $gocKiemThu . ') started')) {
        throw new RuntimeException('Máy chủ kiểm thử không lắng nghe tại địa chỉ đã chọn.');
    }
    echo 'CSDL kiểm thử riêng: ' . $tenCsdl . PHP_EOL;
    [$ma, $than] = gui('/uploads/fixture.php.jpg');
    check($ma === 404 && $than === 'Không tìm thấy.', 'Router chặn tên tệp thực thi có đuôi kép');
    [$ma] = gui('/css/style.css');
    check($ma === 200, 'Router vẫn phục vụ tài nguyên tĩnh');
    echo '=== 0. Đăng nhập chủ trang duy và tài khoản hop ===' . PHP_EOL;
    $tokenDuy = dangNhap($jarDuy, 'duy');
    $tokenHop = dangNhap($jarHop, 'hop');
    [, $trangChu] = gui(TRANG, 'GET', [], [], $jarDuy);
    check(str_contains($trangChu, 'data-xac-nhan="Xóa món này'), 'Chủ trang thấy nút xóa món dùng chung');
    check(str_contains($trangChu, 'name="csrf-token"'), 'Trang có thẻ meta csrf-token');

    echo PHP_EOL . '=== 1. Ca (a) — XSS trong tên món và lời nhắn ===' . PHP_EOL;
    $xss = '<b>Bình</b> " \' &';
    [$ma] = gui(TRANG, 'POST', [
        'hanh_dong' => 'chon_mon',
        'bua' => 'trua',
        'dsMon' => "Phở\n" . $xss . "\nCơm tấm",
        '_csrf' => $tokenDuy,
    ], [], $jarDuy);
    check($ma === 303, 'POST chọn món có ký tự đặc biệt', 'mã ' . $ma);
    [, $sauXss] = gui(TRANG, 'GET', [], [], $jarDuy);
    check(str_contains($sauXss, '&lt;b&gt;Bình&lt;/b&gt;'), 'Trang hiện nguyên văn &lt;b&gt;Bình&lt;/b&gt;');
    check(!str_contains($sauXss, '<b>Bình</b>'), 'Trang không chứa thẻ <b> thật');

    echo PHP_EOL . '=== 2. Ca (b) — CSRF: thiếu mã, mã sai, mã dạng mảng ===' . PHP_EOL;
    $truoc = demMon();
    [$ma] = gui(TRANG, 'POST', [
        'hanh_dong' => 'chon_mon',
        'bua' => 'trua',
        'dsMon' => "Món không mã\nMón thứ hai",
    ], [], $jarDuy);
    check($ma === 403, 'POST thiếu _csrf bị chặn', 'mã ' . $ma);
    check(demMon() === $truoc, 'CSDL không thêm dòng khi thiếu mã', 'trước/sau: ' . $truoc . '/' . demMon());
    [$ma] = gui(TRANG, 'POST', ['hanh_dong' => 'chon_mon', 'dsMon' => "A\nB", '_csrf' => 'abc'], [], $jarDuy);
    check($ma === 403, 'POST _csrf=abc bị chặn', 'mã ' . $ma);
    [$ma] = gui(TRANG, 'POST', ['hanh_dong' => 'chon_mon', 'dsMon' => "A\nB", '_csrf' => ['abc']], [], $jarDuy);
    check($ma === 403, 'POST _csrf[]=abc bị chặn', 'mã ' . $ma);

    echo PHP_EOL . '=== 3. Ca (c) — không phải chủ trang gửi yêu cầu xóa ===' . PHP_EOL;
    $idMonDuy = (int) $pdo->query("SELECT id FROM tv_mon_an WHERE mssv='" . MSSV_DUY . "' ORDER BY id LIMIT 1")->fetchColumn();
    $truoc = demMon();
    [$ma] = gui(TRANG, 'POST', [
        'hanh_dong' => 'xoa_mon',
        'id' => $idMonDuy,
        '_csrf' => $tokenHop,
    ], [], $jarHop);
    $con = (int) $pdo->query('SELECT COUNT(*) FROM tv_mon_an WHERE id=' . $idMonDuy)->fetchColumn();
    check($ma === 403, 'Tài khoản hop gửi xóa món của Duy bị chặn', 'mã ' . $ma);
    check($con === 1, 'Bản ghi của Duy vẫn còn', 'id=' . $idMonDuy);
    check(demMon() === $truoc, 'Số dòng tv_mon_an không đổi', 'trước/sau: ' . $truoc . '/' . demMon());

    echo PHP_EOL . '=== 4. Ca (d) — chủ trang sửa ô ẩn sang id của người khác ===' . PHP_EOL;
    $pdo->prepare("INSERT INTO tv_mon_an (mssv, ten_mon, bua) VALUES (?,?,?)")
        ->execute([MSSV_KHAC, 'Mon fixture kiem thu', 'trua']);
    $idFixture = (int) $pdo->lastInsertId();
    [$ma] = gui(TRANG, 'POST', [
        'hanh_dong' => 'xoa_mon',
        'id' => $idFixture,
        '_csrf' => $tokenDuy,
    ], [], $jarDuy);
    $con = (int) $pdo->query('SELECT COUNT(*) FROM tv_mon_an WHERE id=' . $idFixture)->fetchColumn();
    check($ma === 404, 'Duy gửi id bản ghi của Hải bị từ chối', 'mã ' . $ma);
    check($con === 1, 'Bản ghi của Hải vẫn còn', 'id=' . $idFixture);

    echo PHP_EOL . '=== 5. Ca (e) — tham số dạng mảng không gây 500/Warning ===' . PHP_EOL;
    foreach ([TRANG . '?trang[]=1', TRANG . '?ten[]=abc', TRANG . '?bua[]=trua'] as $u) {
        [$ma, $than] = gui($u, 'GET', [], [], $jarDuy);
        $coLoi = preg_match('/Warning:|Fatal error|Notice:|Deprecated:/', $than) === 1;
        check($ma === 200 && !$coLoi, 'GET ' . $u, 'mã ' . $ma . ', cảnh báo PHP: ' . ($coLoi ? 'có' : 'không'));
    }
    [$ma, $than] = gui(TRANG, 'POST', [
        'hanh_dong' => 'chon_mon',
        'dsMon' => ['a', 'b'],
        'bua' => ['trua'],
        '_csrf' => $tokenDuy,
    ], [], $jarDuy);
    check($ma === 303, 'POST dsMon[]/bua[] dạng mảng được xử lý an toàn', 'mã ' . $ma);
    check(!preg_match('/Warning:|Fatal error/', $than), 'Không có cảnh báo PHP trong phản hồi');

    echo PHP_EOL . '=== 6. Kiểm tra thêm: fetch POST và header X-CSRF-Token ===' . PHP_EOL;
    [$ma, $than, $header] = gui(TRANG, 'POST', ['hanh_dong' => 'choi_oan_tu_ti', 'chon' => 'keo'],
        ['X-Requested-With: XMLHttpRequest'], $jarDuy);
    check($ma === 403, 'AJAX thiếu mã bị chặn', 'mã ' . $ma);
    check(stripos($header, 'Content-Type: application/json') !== false, 'Phản hồi AJAX bị chặn là JSON');
    [$ma, $than] = gui(TRANG, 'POST', ['hanh_dong' => 'choi_oan_tu_ti', 'chon' => 'keo'],
        ['X-Requested-With: XMLHttpRequest', 'X-CSRF-Token: ' . $tokenDuy], $jarDuy);
    $json = json_decode($than, true);
    check($ma === 200 && is_array($json) && ($json['ok'] ?? false) === true, 'AJAX có header X-CSRF-Token đúng trả JSON ok', 'mã ' . $ma);

    echo PHP_EOL . '=== 7. Kiểm tra header bảo mật của trang (Biện pháp 3) ===' . PHP_EOL;
    [$ma, , $header] = gui(TRANG, 'GET', [], [], $jarDuy);
    check($ma === 200, 'Trang trả 200');
    check(stripos($header, 'Content-Security-Policy:') !== false, 'Có Content-Security-Policy');
    check(stripos($header, 'X-Content-Type-Options: nosniff') !== false, 'Có X-Content-Type-Options: nosniff');
    check(stripos($header, 'Referrer-Policy:') !== false, 'Có Referrer-Policy');
    check(stripos($header, 'X-Powered-By') === false, 'Không còn X-Powered-By');
} finally {
    if (is_resource($mayChu)) {
        proc_terminate($mayChu);
        proc_close($mayChu);
    }
    @unlink($jarDuy);
    @unlink($jarHop);
    @unlink($logMayChu);
    try {
        if ($daTaoTaiKhoan) {
            $pdo->exec('DROP USER ' . $taiKhoan);
        }
    } finally {
        if ($daTaoCsdl) {
            $pdo->exec('DROP DATABASE `' . $tenCsdl . '`');
        }
    }
}

$dat = count(array_filter($ketQua));
$tong = count($ketQua);
echo PHP_EOL . "Kết quả: {$dat}/{$tong} ca đạt." . PHP_EOL;
exit($dat === $tong ? 0 : 1);
