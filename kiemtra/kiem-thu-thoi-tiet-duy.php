<?php
declare(strict_types=1);
/**
 * Kiểm tra nâng cấp thời tiết cũ giữ ID/trạng thái/thời gian và thêm MSSV/FK.
 * Thử thống kê riêng từng thành viên, FK, xóa dây chuyền và dữ liệu mồ côi.
 * Chỉ tạo/xóa CSDL ngẫu nhiên: php kiemtra/kiem-thu-thoi-tiet-duy.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';
function kiemTraThoiTiet(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_weather_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo,$ten);
    $pdo->exec('DROP TABLE tv_thoi_tiet_duy');
    $pdo->exec("CREATE TABLE tv_thoi_tiet_duy (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trang_thai ENUM('nang','mua','may','giong_bao') NOT NULL,
        thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
    $pdo->exec("INSERT INTO tv_thoi_tiet_duy VALUES (17,'nang','2026-10-01 08:00:00'),(22,'mua','2026-10-02 09:00:00')");
    $cu = $pdo->query('SELECT id,trang_thai,thoi_gian FROM tv_thoi_tiet_duy ORDER BY id')->fetchAll();
    caiDatNhom($pdo,$ten);
    kiemTraThoiTiet($cu === $pdo->query('SELECT id,trang_thai,thoi_gian FROM tv_thoi_tiet_duy ORDER BY id')->fetchAll(), 'Đã đổi check-in cũ.');
    kiemTraThoiTiet((int) $pdo->query("SELECT COUNT(*) FROM tv_thoi_tiet_duy WHERE mssv='3120224034'")->fetchColumn() === 2, 'Không gán dòng cũ cho Duy.');
    $snapshot = $pdo->query('SELECT * FROM tv_thoi_tiet_duy ORDER BY id')->fetchAll();
    nangCapThoiTietDuy($pdo);
    kiemTraThoiTiet($snapshot === $pdo->query('SELECT * FROM tv_thoi_tiet_duy ORDER BY id')->fetchAll(), 'Chạy lại thay đổi/nhân bản check-in.');
    echo "PASS nâng cấp bảng cũ qua bộ cài giữ ID, trạng thái, thời gian; chạy lại không đổi dữ liệu.\n";

    $kho = new App\Data\KhoCaNhan($pdo);
    $kho->themCheckIn('3120224034','nang');
    $kho->themCheckIn('3120224064','giong_bao');
    $duy = array_column($kho->thongKeThoiTiet('3120224034'),'so_luot','trang_thai');
    $hop = array_column($kho->thongKeThoiTiet('3120224064'),'so_luot','trang_thai');
    kiemTraThoiTiet((int) $duy['nang'] === 2 && (int) $duy['mua'] === 1 && !isset($duy['giong_bao'])
        && (int) $hop['giong_bao'] === 1, 'Thống kê lẫn thành viên.');
    try {
        $kho->themCheckIn('0000000000','nang');
        throw new LogicException('Thiếu FK chặn MSSV không tồn tại.');
    } catch (PDOException $loi) {
        kiemTraThoiTiet((int) $loi->errorInfo[1] === 1452, 'Sai lỗi FK.');
    }
    try {
        $kho->themCheckIn('3120224034','gia');
        throw new LogicException('Chấp nhận trạng thái sai.');
    } catch (DomainException) {}
    $pdo->exec("DELETE FROM thanh_vien WHERE mssv='3120224064'");
    kiemTraThoiTiet($kho->thongKeThoiTiet('3120224064') === []
        && count($kho->thongKeThoiTiet('3120224034')) === 2, 'CASCADE xóa sai dữ liệu.');
    echo "PASS lọc riêng MSSV, FK chặn mã sai, trạng thái sai bị từ chối và CASCADE đúng thành viên.\n";

    $pdo->exec('ALTER TABLE tv_thoi_tiet_duy DROP FOREIGN KEY fk_thoi_tiet_duy_thanh_vien');
    $pdo->exec("INSERT INTO tv_thoi_tiet_duy (mssv,trang_thai) VALUES ('0000000000','may')");
    try {
        nangCapThoiTietDuy($pdo);
        throw new LogicException('Không cảnh báo dòng mồ côi.');
    } catch (RuntimeException $loi) {
        kiemTraThoiTiet(str_contains($loi->getMessage(),'MSSV không tồn tại'), 'Sai cảnh báo dữ liệu cũ.');
    }
    kiemTraThoiTiet((int) $pdo->query("SELECT COUNT(*) FROM tv_thoi_tiet_duy WHERE mssv='0000000000'")->fetchColumn() === 1, 'Tự xóa dòng lỗi.');
    echo "PASS MSSV mồ côi được báo để kiểm tra, không tự xóa dữ liệu.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã dọn CSDL thử riêng.\n";
}
