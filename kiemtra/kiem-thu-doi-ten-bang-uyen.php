<?php
declare(strict_types=1);
/**
 * Kiểm thử đổi tên bảng Uyên trên CSDL ngẫu nhiên, không sửa dữ liệu ứng dụng.
 * Thử schema/seed mới, bảng cũ có dữ liệu riêng, chạy lặp và xung đột hai tên.
 * Chạy: php kiemtra/kiem-thu-doi-ten-bang-uyen.php; cần quyền CREATE/DROP DATABASE.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';

function kiemTraBangUyen(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
function anhBangUyen(PDO $pdo, string $bang): array
{
    return $pdo->query('SELECT * FROM `' . $bang . '` ORDER BY id')->fetchAll();
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_uyen_' . bin2hex(random_bytes(8));
$cacBanSao = [];
try {
    foreach (['schema.sql', 'seed.sql'] as $tep) $pdo->exec(caiDatDocSql($tep, $ten));
    $kho = new App\Data\KhoTrangCaNhanUyen($pdo);
    kiemTraBangUyen(count($kho->layKyNang('3120224174', 'tat-ca')) === 5, 'Schema/seed mới thiếu kỹ năng.');
    kiemTraBangUyen(count($kho->timThoiKhoaBieu('3120224174', '')) === 6, 'Schema/seed mới thiếu thời khóa biểu.');
    $kq = caiDatNhom($pdo, $ten); $cacBanSao[] = $kq['sao_luu'];
    echo "PASS schema/seed mới và bộ cài dùng đúng tên bảng mới.\n";

    $pdo->exec('RENAME TABLE tv_ky_nang TO tv_uyen_ky_nang, tv_thoi_khoa_bieu TO tv_uyen_thoi_khoa_bieu');
    $pdo->exec("UPDATE tv_uyen_ky_nang SET nhom='du-lieu' WHERE ten='HTML5'");
    $pdo->exec("INSERT INTO tv_uyen_ky_nang (id,mssv,ten,nhom) VALUES (701,'3120224174','Kỹ năng riêng','lam-viec')");
    $pdo->exec("INSERT INTO tv_uyen_thoi_khoa_bieu
        (id,mssv,ma_mon,ten_mon,ma_lop,thu,tiet_bat_dau,tiet_ket_thuc,phong)
        VALUES (801,'3120224174','UYENOLD','Môn riêng','LOP-RIENG',3,1,2,'P-RIENG')");
    $kyNang = anhBangUyen($pdo, 'tv_uyen_ky_nang');
    $lich = anhBangUyen($pdo, 'tv_uyen_thoi_khoa_bieu');
    $kq = caiDatNhom($pdo, $ten); $cacBanSao[] = $kq['sao_luu'];
    kiemTraBangUyen($kyNang === anhBangUyen($pdo, 'tv_ky_nang'), 'Đổi ID hoặc dữ liệu kỹ năng cũ.');
    kiemTraBangUyen($lich === anhBangUyen($pdo, 'tv_thoi_khoa_bieu'), 'Đổi ID hoặc lịch cũ.');
    kiemTraBangUyen($kq['so_bang'] === 24, 'Còn bảng cũ hoặc tạo bảng trùng.');
    kiemTraBangUyen(count($kho->timThoiKhoaBieu('3120224174', 'UYENOLD')) === 1, 'Lớp dữ liệu không đọc được lịch đã nâng cấp.');
    try {
        $pdo->exec("INSERT INTO tv_ky_nang (mssv,ten,nhom) VALUES ('0000000000','Sai MSSV','web')");
        throw new LogicException('Mất khóa ngoại sau đổi tên.');
    } catch (PDOException $loi) {
        kiemTraBangUyen((int) ($loi->errorInfo[1] ?? 0) === 1452, 'Sai lỗi khóa ngoại.');
    }
    $kq = caiDatNhom($pdo, $ten); $cacBanSao[] = $kq['sao_luu'];
    kiemTraBangUyen($kyNang === anhBangUyen($pdo, 'tv_ky_nang') && $lich === anhBangUyen($pdo, 'tv_thoi_khoa_bieu'), 'Chạy lại làm đổi dữ liệu.');
    echo "PASS đổi hai bảng cũ: giữ ID, dữ liệu riêng, khóa ngoại và chạy lại không trùng.\n";

    $pdo->exec('RENAME TABLE tv_ky_nang TO tv_uyen_ky_nang');
    $kq = caiDatNhom($pdo, $ten); $cacBanSao[] = $kq['sao_luu'];
    kiemTraBangUyen($kyNang === anhBangUyen($pdo, 'tv_ky_nang'), 'Không nâng cấp được khi chỉ một bảng còn tên cũ.');
    echo "PASS máy đã đổi tên một phần.\n";

    // Xung đột một cặp phải chặn cả việc đổi tên cặp còn lại.
    $pdo->exec('CREATE TABLE tv_uyen_ky_nang LIKE tv_ky_nang');
    $pdo->exec('INSERT INTO tv_uyen_ky_nang SELECT * FROM tv_ky_nang');
    $pdo->exec('RENAME TABLE tv_thoi_khoa_bieu TO tv_uyen_thoi_khoa_bieu');
    $tepTruoc = glob(sys_get_temp_dir() . '/edugpa-truoc-cap-nhat-*.sql');
    try {
        caiDatNhom($pdo, $ten);
        throw new LogicException('Tự xử lý xung đột hai tên bảng.');
    } catch (RuntimeException $loi) {
        kiemTraBangUyen(str_contains($loi->getMessage(), 'Đồng thời tồn tại'), 'Sai thông báo xung đột.');
    } finally {
        foreach (array_diff(glob(sys_get_temp_dir() . '/edugpa-truoc-cap-nhat-*.sql'), $tepTruoc) as $tep) {
            if (str_contains(file_get_contents($tep), 'USE `' . $ten . '`;')) $cacBanSao[] = $tep;
        }
    }
    kiemTraBangUyen($kyNang === anhBangUyen($pdo, 'tv_ky_nang') && $kyNang === anhBangUyen($pdo, 'tv_uyen_ky_nang'), 'Xung đột làm đổi kỹ năng.');
    kiemTraBangUyen($lich === anhBangUyen($pdo, 'tv_uyen_thoi_khoa_bieu'), 'Đổi một phần trước khi báo xung đột.');
    echo "PASS xung đột tên: dừng rõ ràng, không ghi đè/xóa dữ liệu hai bảng.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    foreach ($cacBanSao as $tep) {
        if (is_string($tep) && dirname($tep) === sys_get_temp_dir() && str_starts_with(basename($tep), 'edugpa-truoc-cap-nhat-')) @unlink($tep);
    }
    echo "Đã dọn CSDL và sao lưu thử riêng.\n";
}
