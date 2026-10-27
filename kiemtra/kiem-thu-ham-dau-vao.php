<?php
declare(strict_types=1);
/**
 * Kiểm tra số nguyên đầu vào và token giỏ dùng chung, không gọi CSDL.
 * Đối chiếu ngữ nghĩa bộ kiểm tra cũ của API/giỏ, gồm kiểu sai và biên số nguyên.
 * Chạy CLI: php kiemtra/kiem-thu-ham-dau-vao.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../inc/ham.php';
require_once __DIR__ . '/../inc/csrf.php';

function kiemTraDauVao(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$giaTri = [null, [], ['1'], true, false, 1.0, 1.5, new stdClass(), '', 'abc',
    '1', 1, ' 1 ', '+1', '01', '1.0', '1e2', '0', 0, '-1', -1,
    PHP_INT_MAX, (string) PHP_INT_MAX, PHP_INT_MIN, (string) PHP_INT_MIN, (string) PHP_INT_MAX . '0'];
foreach ([[1, PHP_INT_MAX], [1, 5], [1, 20], [PHP_INT_MIN, PHP_INT_MAX]] as [$min, $max]) {
    foreach ($giaTri as $raw) {
        $cu = is_int($raw) || is_string($raw)
            ? filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range'=>$min, 'max_range'=>$max]])
            : false;
        kiemTraDauVao(soNguyenDauVao($raw, $min, $max) === $cu, 'Đổi hành vi số nguyên đầu vào.');
    }
}
$_SESSION = [];
$token = tokenGioHang();
kiemTraDauVao(strlen($token) === 64 && ctype_xdigit($token), 'Token giỏ sai định dạng.');
kiemTraDauVao(tokenGioHang() === $token && $_SESSION['csrf_gio_hang'] === $token, 'Token giỏ không ổn định.');
foreach ([null, '', [], 1] as $sai) {
    $_SESSION['csrf_gio_hang'] = $sai;
    kiemTraDauVao(strlen(tokenGioHang()) === 64, 'Không khôi phục token giỏ thiếu/sai kiểu.');
}
echo "PASS số nguyên giữ ngữ nghĩa cũ, từ chối kiểu sai; token giỏ dùng chung, ổn định và tự khôi phục.\n";
