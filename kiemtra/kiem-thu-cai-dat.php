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
$tenDo = $ten . '_do';
$tenMerge = $ten . '_merge';
$tenId = $ten . '_id';
$cacBanSao = [];
try {
    $moi = caiDatNhom($pdo, $ten);
    kiemThuCaiDat($moi['cai_moi'] && $moi['so_mon'] === 30, 'Không cài mới đủ môn.');
    $cacBang = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['gio_hang_nguoi_dung','mon_yeu_thich','tv_uyen_ky_nang','tv_uyen_thoi_khoa_bieu'] as $b) {
        kiemThuCaiDat(in_array($b, $cacBang, true), 'Thiếu bảng bắt buộc ' . $b);
    }
    kiemThuCaiDat(count($cacBang) === 20, 'Số bảng không đúng schema.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM thanh_vien')->fetchColumn() === 5, 'Thiếu thành viên.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_uyen_ky_nang')->fetchColumn() === 5, 'Thiếu kỹ năng Uyên.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_uyen_thoi_khoa_bieu')->fetchColumn() === 6, 'Thiếu lịch Uyên.');
    $hai = $pdo->query("SELECT id,mat_khau FROM nguoi_dung WHERE ten_dang_nhap='hai'")->fetch();
    kiemThuCaiDat($hai !== false && password_verify('matkhau123', $hai['mat_khau']), 'Thiếu tài khoản mẫu Hải.');
    kiemThuCaiDat((int) $pdo->query("SELECT nguoi_dung_id FROM thanh_vien WHERE mssv='3120224042'")->fetchColumn() === (int) $hai['id'], 'Thiếu liên kết chủ trang.');
    echo "PASS cài mới: 20 bảng, 30 môn, 5 thành viên, dữ liệu Uyên, tài khoản Hải và chủ trang đúng.\n";

    $st = $pdo->prepare('UPDATE nguoi_dung SET mat_khau=? WHERE id=?');
    $st->execute([password_hash('MatKhauRieng987!', PASSWORD_DEFAULT), $hai['id']]);
    $pdo->exec('INSERT INTO gio_hang_nguoi_dung VALUES (2,1,3)');
    $pdo->exec('INSERT INTO mon_yeu_thich VALUES (2,2)');
    $pdo->exec('UPDATE mon_hoc SET so_cho=7 WHERE id=1');
    $pdo->exec("DELETE FROM tv_luot_xem WHERE ngay<CURRENT_DATE");
    $pdo->exec('UPDATE tv_luot_xem SET so_luot=99');
    $pdo->exec('DELETE FROM tv_binh_chon');
    $pdo->exec("INSERT INTO tv_binh_chon(mssv,nguoi_dung_id,tuy_chon) VALUES ('3120224064',3,'de_hieu')");
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
    $sau = $pdo->query('SELECT ke_hoach_id,mon_hoc_id,so_luong FROM chi_tiet_ke_hoach ORDER BY 1,2')->fetchAll();
    foreach ($chiTietCu as $ct) kiemThuCaiDat(in_array($ct, $sau, true), 'Mất chi tiết kế hoạch.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM chi_tiet_ke_hoach WHERE ma_mon IS NULL OR ten_mon IS NULL')->fetchColumn() === 0, 'Chưa điền snapshot.');
    echo "PASS bản cũ: 8 lên 30 môn, thêm bảng/cột thiếu và chủ trang Hải; giữ tài khoản và kế hoạch.\n";
    $truoc = anhDuLieuCaiDat($pdo);
    $kq = caiDatNhom($pdo, $tenCu); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($truoc === anhDuLieuCaiDat($pdo), 'Nâng cấp lại làm đổi dữ liệu.');
    // Có schema nhưng chưa seed: phải tạo cả admin/an/binh, không chỉ Hải.
    $pdo->exec(caiDatDocSql('schema.sql', $tenDo));
    $kq = caiDatNhom($pdo, $tenDo); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat(count($kq['tai_khoan_moi']) === 4, 'Cài dở không bổ sung đủ tài khoản.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM ke_hoach_hoc_tap')->fetchColumn() === 3, 'Cài dở thiếu kế hoạch.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_luot_xem')->fetchColumn() === 7, 'Cài dở thiếu lượt xem mẫu.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_mon_an')->fetchColumn() === 3, 'Cài dở thiếu món ăn mẫu.');
    $truoc = anhDuLieuCaiDat($pdo);
    $kq = caiDatNhom($pdo, $tenDo); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($truoc === anhDuLieuCaiDat($pdo), 'Cài dở chạy lại tạo trùng.');
    echo "PASS đã có schema/chưa seed: đủ 4 tài khoản, kế hoạch và dữ liệu mẫu; chạy lại không trùng.\n";

    // Mô phỏng 9485c44: mất hai bảng chung, chưa có dữ liệu Uyên trên máy cũ.
    caiDatNhom($pdo, $tenMerge);
    $pdo->exec('DROP TABLE gio_hang_nguoi_dung,mon_yeu_thich');
    $pdo->exec("DELETE FROM thanh_vien WHERE mssv='3120224174'");
    $kq = caiDatNhom($pdo, $tenMerge); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($kq['so_bang'] === 20, 'Không khôi phục được hai bảng chung.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_uyen_ky_nang')->fetchColumn() === 5, 'Nâng cấp thiếu kỹ năng Uyên.');
    kiemThuCaiDat((int) $pdo->query('SELECT COUNT(*) FROM tv_uyen_thoi_khoa_bieu')->fetchColumn() === 6, 'Nâng cấp thiếu lịch Uyên.');
    echo "PASS bản merge lỗi: khôi phục giỏ/yêu thích và dữ liệu Uyên.\n";

    // ID máy cũ khác hoàn toàn seed: liên kết theo username/slug/mã môn/MSSV.
    $pdo->exec(caiDatDocSql('schema.sql', $tenId));
    $st = $pdo->prepare("INSERT INTO nguoi_dung (id,email,ten_dang_nhap,mat_khau,ho_ten,vai_tro) VALUES (1,'rieng@example.com','rieng',?,'Người dùng riêng','quan_tri')");
    $bam = password_hash('MatKhauRieng987!', PASSWORD_DEFAULT); $st->execute([$bam]);
    $pdo->exec("INSERT INTO danh_muc VALUES (101,'Danh mục riêng','rieng')");
    $pdo->exec("INSERT INTO mon_hoc (id,danh_muc_id,ma_mon,ten_mon,so_tin_chi,so_cho) VALUES (501,101,'RIENG101','Môn riêng',3,9),(502,101,'MAT101','Tên đã sửa trên máy cũ',3,4)");
    $pdo->exec("INSERT INTO thanh_vien (mssv,ho_ten,nguoi_dung_id) VALUES ('3120224042','Tên chủ trang đã sửa',1)");
    $monCu = $pdo->query('SELECT * FROM mon_hoc ORDER BY id')->fetchAll();
    $kq = caiDatNhom($pdo, $tenId); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($kq['so_mon'] === 31, 'Môn cùng mã bị nhân đôi hoặc môn riêng bị mất.');
    kiemThuCaiDat($monCu === $pdo->query('SELECT * FROM mon_hoc WHERE id IN (501,502) ORDER BY id')->fetchAll(), 'Ghi đè tên/số chỗ/danh mục môn cũ.');
    kiemThuCaiDat($pdo->query('SELECT mat_khau FROM nguoi_dung WHERE id=1')->fetchColumn() === $bam, 'Ghi đè mật khẩu ID trùng seed.');
    kiemThuCaiDat((int) $pdo->query("SELECT nguoi_dung_id FROM thanh_vien WHERE mssv='3120224042'")->fetchColumn() === 1, 'Ghi đè chủ trang cũ.');
    $nguoiDat = $pdo->query('SELECT u.ten_dang_nhap FROM ke_hoach_hoc_tap k JOIN nguoi_dung u ON u.id=k.nguoi_dung_id ORDER BY u.ten_dang_nhap')->fetchAll(PDO::FETCH_COLUMN);
    kiemThuCaiDat($nguoiDat === ['an','binh','hai'], 'Ánh xạ sai người đặt kế hoạch mẫu.');
    kiemThuCaiDat((int) $pdo->query("SELECT COUNT(*) FROM chi_tiet_ke_hoach WHERE ma_mon='MAT101' AND mon_hoc_id=502")->fetchColumn() === 1, 'Ánh xạ sai ID môn trong chi tiết.');
    $truoc = anhDuLieuCaiDat($pdo);
    $kq = caiDatNhom($pdo, $tenId); $cacBanSao[] = $kq['sao_luu'];
    kiemThuCaiDat($truoc === anhDuLieuCaiDat($pdo), 'ID khác seed chạy lại bị đổi dữ liệu.');
    echo "PASS ID khác seed: giữ tài khoản, chủ trang, môn riêng; ánh xạ đúng kế hoạch/chi tiết.\n";
    try { caiDatNhom($pdo, 'bad;DROP DATABASE x'); throw new LogicException('Chấp nhận tên CSDL sai.'); }
    catch (InvalidArgumentException) {}
    echo "PASS nâng cấp lặp và kiểm tra tên CSDL.\n";
} finally {
    foreach ([$ten, $tenCu, $tenDo, $tenMerge, $tenId] as $csdl) $pdo->exec('DROP DATABASE IF EXISTS `' . $csdl . '`');
    foreach ($cacBanSao as $tep) {
        if (is_string($tep) && dirname($tep) === sys_get_temp_dir() && str_starts_with(basename($tep), 'edugpa-truoc-cap-nhat-')) @unlink($tep);
    }
    echo "Đã dọn dữ liệu kiểm thử; không sửa CSDL thật.\n";
}
