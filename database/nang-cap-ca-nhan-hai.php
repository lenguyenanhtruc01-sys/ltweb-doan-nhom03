<?php
declare(strict_types=1);

/**
 * Nâng cấp avatar Hải sang MySQL và liên kết tài khoản chủ trang, giữ dữ liệu cũ.
 * Sao lưu rồi chạy CLI: php database/nang-cap-ca-nhan-hai.php [ID_chu_trang].
 * Mặc định tạo tài khoản mẫu hai/hai@example.com (matkhau123) nếu chưa có chủ.
 * Chỉ nhập JSON một lần khi avatar DB còn trống; không xóa tệp JSON/ảnh cũ.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403); exit('Chỉ chạy nâng cấp bằng PHP CLI.');
}

function nangCapCaNhanHai(PDO $pdo, ?int $chuTrangId = null, ?string $tepCu = null): array
{
    $mssv = '3120224042';
    $tenCu = null;
    if ($tepCu !== null && is_file($tepCu)) {
        $noiDung = file_get_contents($tepCu);
        if ($noiDung === false) throw new RuntimeException('Không đọc được JSON avatar cũ.');
        $tenCu = json_decode($noiDung, true, 512, JSON_THROW_ON_ERROR);
        if (!is_string($tenCu) || preg_match('/^anh_[a-f0-9]{32}\.(jpg|png|webp)$/D', $tenCu) !== 1) {
            throw new RuntimeException('Tên ảnh cũ không hợp lệ; chưa nhập dữ liệu.');
        }
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS tv_anh_dai_dien (
        mssv CHAR(10) PRIMARY KEY, ten_tep VARCHAR(255) NULL,
        cap_nhat_luc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE)');
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT nguoi_dung_id FROM thanh_vien WHERE mssv=? FOR UPDATE');
        $st->execute([$mssv]);
        $tv = $st->fetch(PDO::FETCH_ASSOC);
        if ($tv === false) throw new RuntimeException('Cần bổ sung dòng thành viên Hải trước khi nâng cấp.');
        $chuCu = $tv['nguoi_dung_id'];
        if ($chuTrangId !== null && $chuCu !== null && (int) $chuCu !== $chuTrangId) {
            throw new RuntimeException('Trang đã có chủ; không tự thay tài khoản hiện có.');
        }
        $uid = $chuCu === null ? $chuTrangId : (int) $chuCu;
        $taoTaiKhoan = false;
        if ($uid === null) {
            $st = $pdo->prepare('SELECT id, email, ten_dang_nhap FROM nguoi_dung WHERE email=? OR ten_dang_nhap=?');
            $st->execute(['hai@example.com', 'hai']);
            $ds = $st->fetchAll(PDO::FETCH_ASSOC);
            if (count($ds) > 1 || ($ds !== [] && ($ds[0]['email'] !== 'hai@example.com' || $ds[0]['ten_dang_nhap'] !== 'hai'))) {
                throw new RuntimeException('Tên/email hai đã được sử dụng; chạy lại với ID tài khoản chủ trang.');
            }
            if ($ds !== []) $uid = (int) $ds[0]['id'];
            else {
                $st = $pdo->prepare('INSERT INTO nguoi_dung (email,ten_dang_nhap,mat_khau,ho_ten,vai_tro)
                    VALUES (?,?,?,?,?)');
                $st->execute(['hai@example.com', 'hai', password_hash('matkhau123', PASSWORD_DEFAULT), 'Lê Dương Hoàng Hải', 'khach_hang']);
                $uid = (int) $pdo->lastInsertId();
                $taoTaiKhoan = true;
            }
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM nguoi_dung WHERE id=?');
        $st->execute([$uid]);
        if ((int) $st->fetchColumn() !== 1) throw new RuntimeException('Tài khoản chủ trang không tồn tại.');
        if ($chuCu === null) {
            $st = $pdo->prepare('UPDATE thanh_vien SET nguoi_dung_id=? WHERE mssv=? AND nguoi_dung_id IS NULL');
            $st->execute([$uid, $mssv]);
        }
        $st = $pdo->prepare('INSERT INTO tv_anh_dai_dien (mssv,ten_tep) VALUES (?,?)
            ON DUPLICATE KEY UPDATE ten_tep=COALESCE(tv_anh_dai_dien.ten_tep, VALUES(ten_tep))');
        $st->execute([$mssv, $tenCu]);
        $pdo->commit();
        return ['chu_trang_id'=>$uid, 'tao_tai_khoan'=>$taoTaiKhoan, 'co_avatar_cu'=>$tenCu !== null];
    } catch (Throwable $loi) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $loi;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        require_once __DIR__ . '/../inc/db.php';
        $id = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : null;
        if ($id === false) throw new RuntimeException('ID chủ trang phải là số nguyên dương.');
        $kq = nangCapCaNhanHai(db(), $id, __DIR__ . '/../storage/3120224042_avatar.json');
        echo json_encode($kq, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
        echo "Nâng cấp hoàn tất; không thay mật khẩu tài khoản đã có hoặc ghi đè avatar mới.\n";
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Nâng cấp thất bại: ' . $loi->getMessage() . PHP_EOL); exit(1);
    }
}
