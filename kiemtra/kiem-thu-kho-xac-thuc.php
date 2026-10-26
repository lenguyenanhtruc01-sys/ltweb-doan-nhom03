<?php
declare(strict_types=1);
/**
 * Kiểm tra lớp tài khoản/phiên trên CSDL ngẫu nhiên, không sửa dữ liệu thật.
 * Thử tìm đăng nhập, hash, hết hạn, thu hồi phiên và rollback đặt lại mật khẩu.
 * Chạy CLI: php kiemtra/kiem-thu-kho-xac-thuc.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';

function kiemTraKhoXacThuc(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_auth_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo, $ten);
    $nguoi = new App\Data\KhoNguoiDung($pdo);
    $phien = new App\Data\KhoPhienGhiNho($pdo);
    $hop = $nguoi->timDeDangNhap('hop');
    kiemTraKhoXacThuc($hop !== null && $nguoi->timDeDangNhap('HOP@EXAMPLE.COM')['id'] === $hop['id'], 'Tên/email không cùng tài khoản.');
    $uid = (int) $hop['id'];
    kiemTraKhoXacThuc($nguoi->timDeDangNhap("' OR 1=1 --") === null, 'Đầu vào SQL không được coi là văn bản.');
    $congKhai = $nguoi->timTheoId($uid);
    kiemTraKhoXacThuc($congKhai !== null && !isset($congKhai['mat_khau']) && $nguoi->timTheoId(99999999) === null, 'Sai dữ liệu người dùng hiện tại.');
    $hash = password_hash('AuditPass123!', PASSWORD_DEFAULT);
    $nguoi->capNhatMatKhauBam($uid, $hash);
    kiemTraKhoXacThuc(password_verify('AuditPass123!', $nguoi->timDeDangNhap('hop')['mat_khau']), 'Không cập nhật được hash.');
    echo "PASS tìm tài khoản, đầu vào SQL, thông tin công khai và cập nhật hash.\n";

    $selector = str_repeat('a',18);
    $tokenHash = hash('sha256', 'audit-token');
    $phien->them($selector, $uid, $tokenHash, time()+3600);
    kiemTraKhoXacThuc($phien->timConHan($selector)['token_hash'] === $tokenHash, 'Không đọc được phiên/hash.');
    $phien->them(str_repeat('b',18), $uid, $tokenHash, time()-60);
    kiemTraKhoXacThuc($phien->timConHan(str_repeat('b',18)) === null, 'Chấp nhận phiên hết hạn.');
    $phien->xoaTheoSelector(str_repeat('b',18));
    $phien->them(str_repeat('c',18), 3, $tokenHash, time()+3600);

    $token = bin2hex(random_bytes(32));
    $nguoi->taoYeuCauDatLai('hop@example.com', hash('sha256',$token));
    $pdo->exec("CREATE TRIGGER audit_chan_thu_hoi BEFORE DELETE ON phien_ghi_nho
        FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='audit rollback'");
    try {
        $nguoi->datLaiMatKhau($token, password_hash('NewAuditPass123!', PASSWORD_DEFAULT));
        throw new LogicException('Không phát sinh lỗi thu hồi phiên.');
    } catch (PDOException $e) {
        kiemTraKhoXacThuc($e->getCode() === '45000', 'Sai lỗi kiểm thử rollback.');
    }
    kiemTraKhoXacThuc(!$pdo->inTransaction() && $nguoi->timDeDangNhap('hop')['mat_khau'] === $hash
        && $nguoi->timYeuCauDatLai($token) !== null && $phien->timConHan($selector) !== null,
        'Lỗi thu hồi phiên không rollback mật khẩu/mã/phiên.');
    $pdo->exec('DROP TRIGGER audit_chan_thu_hoi');
    kiemTraKhoXacThuc($nguoi->datLaiMatKhau($token, password_hash('NewAuditPass123!', PASSWORD_DEFAULT)), 'Đặt lại thất bại.');
    kiemTraKhoXacThuc($phien->timConHan($selector) === null && $phien->timConHan(str_repeat('c',18)) !== null,
        'Thu hồi sai tài khoản.');
    kiemTraKhoXacThuc(!$nguoi->datLaiMatKhau($token,$hash), 'Dùng lại được mã đặt lại.');
    echo "PASS phiên hợp lệ/hết hạn; đặt lại thu hồi đúng tài khoản và rollback khi có lỗi.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã xóa CSDL kiểm thử riêng.\n";
}
