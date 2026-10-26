<?php
declare(strict_types=1);

// Chỉ dùng CSDL ngẫu nhiên; không sửa CSDL ứng dụng.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';

function kiemTraChuHai(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_owner_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo, $ten);
    $hai = (int) $pdo->query("SELECT id FROM nguoi_dung WHERE email='hai@example.com'")->fetchColumn();
    $khac = (int) $pdo->query("SELECT id FROM nguoi_dung WHERE ten_dang_nhap='hop'")->fetchColumn();
    $doiChu = $pdo->prepare("UPDATE thanh_vien SET nguoi_dung_id=? WHERE mssv='3120224042'");
    $layChu = static fn (): int => (int) $pdo->query("SELECT nguoi_dung_id FROM thanh_vien WHERE mssv='3120224042'")->fetchColumn();
    $anh = 'anh_' . str_repeat('a', 32) . '.png';
    $pdo->prepare("UPDATE tv_anh_dai_dien SET ten_tep=? WHERE mssv='3120224042'")->execute([$anh]);
    $matKhau = $pdo->query("SELECT mat_khau FROM nguoi_dung WHERE id=$hai")->fetchColumn();

    nangCapCaNhanHai($pdo);
    kiemTraChuHai($layChu() === $hai, 'Chủ đúng bị đổi.');
    $doiChu->execute([null]);
    nangCapCaNhanHai($pdo);
    kiemTraChuHai($layChu() === $hai, 'Không gán được chủ còn trống.');
    echo "PASS chủ đúng và chủ trống.\n";

    $doiChu->execute([$khac]);
    try {
        caiDatNhom($pdo, $ten);
        throw new LogicException('Bộ cài chấp nhận chủ sai.');
    } catch (RuntimeException $e) {
        kiemTraChuHai(str_contains($e->getMessage(), 'Liên kết chủ trang Hải không khớp')
            && str_contains($e->getMessage(), 'ID ' . $khac)
            && str_contains($e->getMessage(), 'ID ' . $hai), 'Không báo rõ xung đột: ' . $e->getMessage());
    }
    kiemTraChuHai(!$pdo->inTransaction() && $layChu() === $khac, 'Đổi chủ sai hoặc chưa rollback.');
    kiemTraChuHai((new App\Data\KhoAnhDaiDien($pdo))->lay('3120224042') === $anh, 'Đã đổi ảnh.');
    kiemTraChuHai($pdo->query("SELECT mat_khau FROM nguoi_dung WHERE id=$hai")->fetchColumn() === $matKhau, 'Đã đổi mật khẩu.');
    echo "PASS bộ cài dừng khi sai chủ, báo hai ID, giữ liên kết/ảnh/mật khẩu.\n";

    nangCapCaNhanHai($pdo, $khac);
    try {
        nangCapCaNhanHai($pdo, $hai);
        throw new LogicException('ID tường minh ghi đè chủ khác.');
    } catch (RuntimeException $e) {
        kiemTraChuHai(str_contains($e->getMessage(), 'không tự thay'), 'Sai lỗi ID tường minh.');
    }
    kiemTraChuHai($layChu() === $khac, 'ID tường minh làm đổi chủ.');
    echo "PASS ID tường minh xác nhận chủ hiện có, không ghi đè chủ khác.\n";

    $doiChu->execute([null]);
    $pdo->exec("UPDATE nguoi_dung SET email='hai-cu@example.com',ten_dang_nhap='hai-cu' WHERE id=$hai");
    $pdo->exec("INSERT INTO nguoi_dung (id,email,ten_dang_nhap,mat_khau,ho_ten,vai_tro)
        SELECT 704,'hai@example.com','hai',mat_khau,ho_ten,vai_tro FROM nguoi_dung WHERE id=$hai");
    nangCapCaNhanHai($pdo);
    kiemTraChuHai($layChu() === 704, 'Phụ thuộc ID cố định.');
    echo "PASS ID tài khoản khác seed vẫn liên kết đúng.\n";

    $doiChu->execute([$khac]);
    $pdo->exec("UPDATE nguoi_dung SET email='khac@example.com',ten_dang_nhap='khac' WHERE id=704");
    try {
        nangCapCaNhanHai($pdo);
        throw new LogicException('Chấp nhận chủ khác khi thiếu tài khoản Hải.');
    } catch (RuntimeException $e) {
        kiemTraChuHai(str_contains($e->getMessage(), 'chưa có tài khoản'), 'Không cảnh báo thiếu tài khoản.');
    }
    kiemTraChuHai($layChu() === $khac, 'Thiếu tài khoản làm đổi chủ.');
    echo "PASS thiếu tài khoản đối chiếu thì dừng, giữ chủ hiện có.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã xóa CSDL kiểm thử riêng.\n";
}
