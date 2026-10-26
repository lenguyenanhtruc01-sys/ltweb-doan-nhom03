<?php
declare(strict_types=1);
/**
 * Kiểm tra danh sách món và xác định chủ trang Duy trên CSDL ngẫu nhiên.
 * Thử đầu vào sai, chống trùng và không nhận UID giả từ biểu mẫu.
 * Chạy CLI: php kiemtra/kiem-thu-chon-mon-duy.php; không sửa CSDL ứng dụng.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Data\KhoCaNhan;

function kiemTraMonDuy(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_food_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo, $ten);
    $kho = new KhoCaNhan($pdo);
    kiemTraMonDuy(!$kho->laChuTrang('3120224034', 1), 'Trang chưa gán chủ nhưng cho phép admin lưu.');
    $pdo->exec("UPDATE thanh_vien SET nguoi_dung_id=1 WHERE mssv='3120224034'");
    kiemTraMonDuy($kho->laChuTrang('3120224034', 1), 'Không nhận chủ đã liên kết.');
    kiemTraMonDuy(!$kho->laChuTrang('3120224034', 2) && !$kho->laChuTrang('3120224034', 0), 'Khách/người khác được coi là chủ.');
    echo "PASS quyền lưu món dựa vào liên kết chủ trang, không dựa vai trò admin.\n";
    $ds = ['Món mới A', 'Món mới B'];
    $kho->luuDanhSachMon('3120224034', $ds, 'trua');
    for ($i=0; $i<100; $i++) {
        kiemTraMonDuy(in_array($kho->chonMonNgauNhien($ds, 'trua'), $ds, true), 'Chọn món ngoài danh sách gửi lên.');
    }
    $kho->luuDanhSachMon('3120224034', [' Món mới A ', '', 'Món mới B', 'Món mới A'], 'trua');
    kiemTraMonDuy((int) $pdo->query("SELECT COUNT(*) FROM tv_mon_an WHERE mssv='3120224034'")->fetchColumn() === 8, 'Chạy lại hoặc dòng trùng sinh thêm món.');
    echo "PASS chỉ chọn danh sách hiện tại, loại dòng trống/trùng, lưu lại không trùng.\n";

    $truoc = $pdo->query('SELECT * FROM tv_mon_an ORDER BY id')->fetchAll();
    $sai = [
        [[], 'trua'], [['Một món'], 'trua'], [['Món A', 'Món A'], 'trua'],
        [array_map(static fn ($n) => 'Món ' . $n, range(1,51)), 'trua'],
        [[str_repeat('á',101), 'Món B'], 'trua'],
        [['Món A', 'Món B'], 'gia'], [['Món A', ['Món B']], 'trua'],
        [["Món\0A", 'Món B'], 'trua'],
        [array_map(static fn ($n) => str_repeat('á',98) . $n, range(1,40)), 'trua'],
    ];
    foreach ($sai as [$mon, $bua]) {
        try {
            $kho->luuDanhSachMon('3120224034', $mon, $bua);
            throw new LogicException('Chấp nhận dữ liệu món sai.');
        } catch (DomainException) {}
        kiemTraMonDuy($truoc === $pdo->query('SELECT * FROM tv_mon_an ORDER BY id')->fetchAll(), 'Dữ liệu sai làm đổi CSDL.');
    }
    echo "PASS dữ liệu sai bị từ chối trước khi ghi, CSDL giữ nguyên.\n";
    $bien = array_map(static fn ($n) => 'Món ' . $n, range(1,50));
    kiemTraMonDuy(count(KhoCaNhan::kiemTraDanhSachMon($bien,'dem')) === 50, 'Từ chối 50 món.');
    kiemTraMonDuy(KhoCaNhan::kiemTraDanhSachMon([str_repeat('á',100),'Món B'],'sang')[0] === str_repeat('á',100), 'Từ chối tên Unicode 100 ký tự.');
    echo "PASS giới hạn 50 món và tên Unicode 100 ký tự.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã xóa CSDL thử riêng.\n";
}
