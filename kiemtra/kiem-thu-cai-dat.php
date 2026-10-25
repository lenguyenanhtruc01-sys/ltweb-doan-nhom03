<?php
declare(strict_types=1);

/**
 * Kiểm thử bộ cài trên hai CSDL ngẫu nhiên; không truy cập dữ liệu ứng dụng thật.
 * Chạy: php kiemtra/kiem-thu-cai-dat.php, cần quyền CREATE/DROP DATABASE.
 * Thử cài mới, nâng cấp cấu trúc cũ, giữ mật khẩu/giỏ và chạy lại nhiều lần.
 * Dọn CSDL thử và bản sao lưu của chính lượt thử trong finally.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Chỉ chạy bằng PHP CLI.'); }
require_once __DIR__ . '/../database/cai-dat.php';

function kiemThuCaiDat(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}

function anhDuLieuCaiDat(PDO $pdo): array
{
    $anh = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $bang) {
        $dong = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $bang) . '`')->fetchAll();
        $dong = array_map('serialize', $dong); sort($dong);
        $anh[$bang] = hash('sha256', serialize($dong));
    }
    ksort($anh);
    return $anh;
}

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'), getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_setup_' . bin2hex(random_bytes(6));
$tenCu = $ten . '_cu';
$cacBanSao = [];
try {
    $moi = caiDatNhom($pdo, $ten);
    kiemThuCaiDat($moi['cai_moi'] && $moi['so_mon'] === 30, 'Không cài mới đủ môn.');
    kiemThuCaiDat(count($pdo->query('SHOW TABLES')->fetchAll()) === 18, 'Thiếu bảng.');
    $hai = $pdo->query("SELECT id,mat_khau FROM nguoi_dung WHERE ten_dang_nhap='hai'")->fetch();
    kiemThuCaiDat($hai !== false && password_verify('matkhau123', $hai['mat_khau']), 'Thiếu tài khoản mẫu Hải.');
    kiemThuCaiDat((int) $pdo->query("SELECT nguoi_dung_id FROM thanh_vien WHERE mssv='3120224042'")->fetchColumn() === (int) $hai['id'], 'Thiếu liên kết chủ trang.');
    echo "PASS cài mới: 18 bảng, 30 môn, tài khoản Hải và chủ trang đúng.\n";

    $st = $pdo->prepare('UPDATE nguoi_dung SET mat_khau=? WHERE id=?');
    $st->execute([password_hash('MatKhauRieng987!', PASSWORD_DEFAULT), $hai['id']]);
    $pdo->exec('INSERT INTO gio_hang_nguoi_dung VALUES (2,1,3)');
    $pdo->exec('INSERT INTO mon_yeu_thich VALUES (2,2)');
    $pdo->exec('UPDATE mon_hoc SET so_cho=7 WHERE id=1');
    $truoc = anhDuLieuCaiDat($pdo);
    foreach ([1,2] as $lan) {
        $kq = caiDatNhom($pdo, $ten);
        $cacBanSao[] = $kq['sao_luu'];
        kiemThuCaiDat(!$kq['cai_moi'] && !$kq['hai']['tao_tai_khoan'], 'Chạy lại tạo trùng dữ liệu.');
        kiemThuCaiDat($truoc === anhDuLieuCaiDat($pdo), 'Chạy lại làm đổi dữ liệu/mật khẩu.');
        kiemThuCaiDat(is_string($kq['sao_luu']) && filesize($kq['sao_luu']) > 0, 'Chưa có bản sao lưu.');
    }
    echo "PASS chạy lại hai lần: giữ mật khẩu riêng, giỏ, yêu thích, số chỗ và toàn bộ dòng cũ; có sao lưu.\n";

    caiDatNhom($pdo, $tenCu);
    // Mô phỏng bản nhóm cũ: 8 môn, chưa có email/số chỗ/snapshot và bảng mới.
    $pdo->exec('DELETE FROM ke_hoach_hoc_tap WHERE id=3');
    $pdo->exec('DELETE FROM mon_hoc WHERE id>8');
    $pdo->exec("UPDATE thanh_vien SET nguoi_dung_id=NULL WHERE mssv='3120224042'");
    $pdo->exec('DELETE FROM nguoi_dung WHERE id=4');
    foreach (['tv_anh_dai_dien','gio_hang_nguoi_dung','mon_yeu_thich','phien_ghi_nho','yeu_cau_dat_lai_mat_khau'] as $bang) $pdo->exec('DROP TABLE `' . $bang . '`');
    $pdo->exec('ALTER TABLE nguoi_dung DROP COLUMN email');
    $pdo->exec('ALTER TABLE mon_hoc DROP COLUMN so_cho');
    $pdo->exec('ALTER TABLE ke_hoach_hoc_tap DROP COLUMN trang_thai, DROP COLUMN ghi_chu');
    $pdo->exec('ALTER TABLE chi_tiet_ke_hoach DROP COLUMN ma_mon, DROP COLUMN ten_mon, DROP COLUMN so_tin_chi');
    $usersCu = $pdo->query('SELECT id,mat_khau FROM nguoi_dung ORDER BY id')->fetchAll();
    $chiTietCu = $pdo->query('SELECT ke_hoach_id,mon_hoc_id,so_luong FROM chi_tiet_ke_hoach ORDER BY 1,2')->fetchAll();
    $kq = caiDatNhom($pdo, $tenCu);
    $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($kq['so_mon'] === 30 && $kq['hai']['tao_tai_khoan'], 'Nâng cấp thiếu môn hoặc tài khoản Hải.');
    foreach ($usersCu as $u) {
        $st = $pdo->prepare('SELECT mat_khau FROM nguoi_dung WHERE id=?'); $st->execute([$u['id']]);
        kiemThuCaiDat($st->fetchColumn() === $u['mat_khau'], 'Đã thay mật khẩu cũ.');
    }
    kiemThuCaiDat($chiTietCu === $pdo->query('SELECT ke_hoach_id,mon_hoc_id,so_luong FROM chi_tiet_ke_hoach ORDER BY 1,2')->fetchAll(), 'Mất chi tiết kế hoạch.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM chi_tiet_ke_hoach WHERE ma_mon IS NULL OR ten_mon IS NULL')->fetchColumn() === 0, 'Chưa điền snapshot.');
    echo "PASS bản cũ: 8 lên 30 môn, thêm bảng/cột thiếu và chủ trang Hải; giữ tài khoản và kế hoạch.\n";
    $truoc = anhDuLieuCaiDat($pdo);
    $kq = caiDatNhom($pdo, $tenCu); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($truoc === anhDuLieuCaiDat($pdo), 'Nâng cấp lại làm đổi dữ liệu.');
    try { caiDatNhom($pdo, 'bad;DROP DATABASE x'); throw new LogicException('Chấp nhận tên CSDL sai.'); }
    catch (InvalidArgumentException) {}
    echo "PASS nâng cấp lặp và kiểm tra tên CSDL.\n";
} finally {
    foreach ([$ten, $tenCu] as $csdl) $pdo->exec('DROP DATABASE IF EXISTS `' . $csdl . '`');
    foreach ($cacBanSao as $tep) {
        if (is_string($tep) && dirname($tep) === sys_get_temp_dir() && str_starts_with(basename($tep), 'edugpa-truoc-cap-nhat-')) @unlink($tep);
    }
    echo "Đã dọn dữ liệu kiểm thử; không sửa CSDL thật.\n";
}
