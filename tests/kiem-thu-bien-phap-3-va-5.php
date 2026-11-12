<?php
declare(strict_types=1);

/**
 * tests/kiem-thu-bien-phap-3-va-5.php
 *
 * Kiểm tra tự động hai biện pháp Duy phụ trách:
 *   - Biện pháp 3: header bảo mật + CSP + không còn script/style nội tuyến.
 *   - Biện pháp 5: .htaccess chặn tệp nội bộ (chỉ kiểm được khi chạy Apache).
 *
 * Cách dùng (website đang chạy):
 *   php tests/kiem-thu-bien-phap-3-va-5.php                    # http://localhost:8000
 *   php tests/kiem-thu-bien-phap-3-va-5.php http://localhost:8080 --htaccess
 *
 * Script chỉ gửi request tới website của nhóm trên máy cá nhân.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

$goc = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$diaChi = parse_url($goc);
if ($diaChi === false
    || !in_array($diaChi['scheme'] ?? '', ['http', 'https'], true)
    || !in_array($diaChi['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true)
    || isset($diaChi['user']) || isset($diaChi['query']) || isset($diaChi['fragment'])) {
    fwrite(STDERR, "Chỉ nhận URL HTTP(S) của website trên máy cục bộ.\n");
    exit(1);
}
$kiemHtaccess = in_array('--htaccess', $argv, true);
$dat = 0;
$tong = 0;
$loi = [];

function kiem(bool $ok, string $ten, string $chiTiet = ''): void
{
    global $dat, $tong, $loi;
    $tong++;
    if ($ok) {
        $dat++;
    } else {
        $loi[] = $ten . ($chiTiet !== '' ? ' — ' . $chiTiet : '');
    }
    printf("  %s %s%s\n", $ok ? '[ĐẠT]' : '[SAI]', $ten, $chiTiet !== '' ? ' — ' . $chiTiet : '');
}

/** Trả về [mã, header, thân]. */
function lay(string $url, string $jar = '', array $post = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $post === [] ? 'GET' : 'POST',
    ]);
    if ($jar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($post !== []) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = curl_exec($ch);
    $ma = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $dai = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $raw = is_string($raw) ? $raw : '';
    return [$ma, substr($raw, 0, $dai), substr($raw, $dai)];
}

echo "== Biện pháp 3: header bảo mật và CSP (" . $goc . ") ==\n\n";

$trang = [
    '/index.php', '/danh-sach.php', '/chi-tiet.php?id=2', '/gio-hang.php', '/lien-he.php',
    '/dang-nhap.php', '/dang-ky.php', '/quen-mat-khau.php',
    '/thanhvien/3120224034_Duy/gioithieu.php', '/thanhvien/3120224042_hai/gioithieu.php',
    '/thanhvien/3120224064_hop/gioithieu.php', '/thanhvien/3120224159_anhtruc/gioithieu.php',
    '/thanhvien/3120224174_uyen/gioithieu.php',
    '/api/san-pham.php?q=lap+trinh', '/api/gio-hang.php',
];

$kiemDau = null;
foreach ($trang as $duongDan) {
    [$ma, $header, $than] = lay($goc . $duongDan);
    $style = preg_match_all('/\sstyle\s*=/', $than);
    $script = preg_match_all('/<script(?![^>]*\bsrc=)/i', $than);
    $canhBao = preg_match('/Warning:|Notice:|Deprecated:|Fatal error|Parse error/', $than) === 1;
    $coCsp = stripos($header, 'Content-Security-Policy:') !== false;
    $coNosniff = stripos($header, 'X-Content-Type-Options: nosniff') !== false;
    $coReferrer = stripos($header, 'Referrer-Policy:') !== false;
    $khongPowered = stripos($header, 'X-Powered-By') === false;
    $datHet = $ma === 200 && $style === 0 && $script === 0 && !$canhBao
        && $coCsp && $coNosniff && $coReferrer && $khongPowered;
    if ($kiemDau === null && $coCsp) {
        foreach (explode("\n", $header) as $dongHeader) {
            if (stripos($dongHeader, 'Content-Security-Policy:') === 0) {
                $kiemDau = trim(substr($dongHeader, strlen('Content-Security-Policy:')));
                break;
            }
        }
    }
    kiem(
        $datHet,
        $duongDan,
        sprintf(
            'HTTP %d, style=%d, script=%d, cảnh báo=%s, CSP=%s, nosniff=%s, Referrer=%s, X-Powered-By=%s',
            $ma, $style, $script, $canhBao ? 'có' : 'không',
            $coCsp ? 'có' : 'KHÔNG', $coNosniff ? 'có' : 'KHÔNG',
            $coReferrer ? 'có' : 'KHÔNG', $khongPowered ? 'không' : 'CÒN'
        )
    );
}

// Trang cần đăng nhập quản trị.
$jar = sys_get_temp_dir() . '/bt7-bp35-' . bin2hex(random_bytes(4)) . '.cookie';
try {
    [$ma, , $login] = lay($goc . '/dang-nhap.php', $jar);
    if ($ma !== 200 || !preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $login, $m)) {
        kiem(false, 'Chuẩn bị kiểm thử trang quản trị', 'Không lấy được CSRF (HTTP ' . $ma . ')');
    } else {
        [$ma] = lay($goc . '/dang-nhap.php', $jar, [
            'email' => 'admin', 'mat_khau' => 'matkhau123', '_csrf' => $m[1],
        ]);
        if ($ma !== 303) {
            kiem(false, 'Chuẩn bị kiểm thử trang quản trị', 'Đăng nhập không thành công (HTTP ' . $ma . ')');
        } else {
            foreach (['/quan-tri.php', '/quan-tri-mon-hoc.php', '/don-hang.php', '/don-hang.php?id=1'] as $duongDan) {
                [$ma, $header, $than] = lay($goc . $duongDan, $jar);
                $style = preg_match_all('/\sstyle\s*=/', $than);
                $script = preg_match_all('/<script(?![^>]*\bsrc=)/i', $than);
                $coCsp = stripos($header, 'Content-Security-Policy:') !== false;
                kiem(
                    $ma === 200 && $style === 0 && $script === 0 && $coCsp,
                    $duongDan . ' (admin)',
                    sprintf('HTTP %d, style=%d, script=%d, CSP=%s', $ma, $style, $script, $coCsp ? 'có' : 'KHÔNG')
                );
            }
        }
    }
} finally {
    if (is_file($jar)) unlink($jar);
}

echo "\n  Chính sách CSP đang gửi:\n  " . wordwrap((string) $kiemDau, 96, "\n  ") . "\n";
kiem(preg_match('/unsafe-inline|unsafe-eval/', (string) $kiemDau) !== 1, 'CSP không chứa unsafe-inline/unsafe-eval');
kiem(!preg_match('/\s\*|;\s*\*/', (string) $kiemDau), 'CSP không dùng dấu *');

if ($kiemHtaccess) {
    echo "\n== Biện pháp 5: .htaccess trên Apache (" . $goc . ") ==\n\n";
    $bang = [
        '/.env' => 404,
        '/.git/config' => 404,
        '/inc/db.php' => 404,
        '/vendor/autoload.php' => 404,
        '/database/schema.sql' => 403,
        '/logs/bao-mat.log' => 403,
        '/composer.json' => 403,
        '/uploads/' => 403,
        '/images/' => 403,
        '/css/style.css' => 200,
        '/images/AI401.png' => 200,
        '/index.php' => 200,
    ];
    foreach ($bang as $duongDan => $mongDoi) {
        [$ma] = lay($goc . $duongDan);
        kiem($ma === $mongDoi, $duongDan, 'mong đợi ' . $mongDoi . ', thực tế ' . $ma);
    }
    // Tệp có đuôi .php chỉ chứa văn bản để kiểm tra quy tắc chặn truy cập.
    $duongDanThu = '/uploads/kiem-tra-bp5-' . bin2hex(random_bytes(8)) . '.php';
    $tepThu = dirname(__DIR__) . $duongDanThu;
    $tep = @fopen($tepThu, 'xb');
    if ($tep === false) {
        kiem(false, 'Tệp kiểm thử Apache', 'Không tạo được tệp riêng trong uploads/');
    } else {
        try {
            $noiDungThu = 'EDUGPA_TEST_FIXTURE';
            $daGhi = fwrite($tep, $noiDungThu);
            fclose($tep);
            if ($daGhi !== strlen($noiDungThu)) {
                kiem(false, 'Tệp kiểm thử Apache', 'Không ghi đủ nội dung thử');
            } else {
                [$ma, , $than] = lay($goc . $duongDanThu);
                kiem($ma === 403 && !str_contains($than, $noiDungThu), $duongDanThu, 'mong đợi 403, thực tế ' . $ma);
            }
        } finally {
            if (is_resource($tep)) fclose($tep);
            unlink($tepThu);
        }
    }
}

echo "\n== Kết quả: {$dat}/{$tong} kiểm tra đạt ==\n";
if ($loi !== []) {
    echo "Chưa đạt:\n";
    foreach ($loi as $dong) {
        echo '  - ' . $dong . "\n";
    }
}
exit($dat === $tong ? 0 : 1);
