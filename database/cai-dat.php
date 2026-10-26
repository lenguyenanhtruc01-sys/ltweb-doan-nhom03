<?php
declare(strict_types=1);

/**
 * Một lệnh tạo/nâng cấp CSDL nhóm, không xóa CSDL dự án hay ghi đè dữ liệu cũ.
 * Chạy tại gốc dự án: php database/cai-dat.php; MySQL phải đang chạy.
 * Dùng CSDL mẫu tạm để gộp seed theo khóa tự nhiên; chỉ dọn CSDL tạm do bộ cài tạo.
 * Thử trên CSDL riêng: cài mới, thiếu bảng/cột, chạy lại và giữ mật khẩu đã đổi.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy cài đặt bằng PHP CLI.');
}
require_once __DIR__ . '/nang-cap-ke-hoach.php';
require_once __DIR__ . '/nang-cap-khoa-ngoai-mon-hoc.php';
require_once __DIR__ . '/nang-cap-so-cho.php';
require_once __DIR__ . '/nang-cap-ca-nhan-hai.php';
require_once __DIR__ . '/nang-cap-ca-nhan-uyen.php';
require_once __DIR__ . '/nang-cap-lien-he.php';
require_once __DIR__ . '/nang-cap-thoi-tiet-duy.php';

function caiDatDocSql(string $tep, string $tenCsdl): string
{
    $sql = @file_get_contents(__DIR__ . '/' . $tep);
    if ($sql === false) throw new RuntimeException('Không đọc được database/' . $tep);
    return str_replace('ltweb_nhom03', $tenCsdl, $sql);
}

function caiDatSaoLuu(PDO $pdo, string $tenCsdl): string
{
    // Đặt ngoài webroot: không lộ bản sao tài khoản khi dùng php -S hoặc Apache.
    $tep = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'edugpa-truoc-cap-nhat-'
        . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.sql';
    $f = fopen($tep, 'xb');
    if ($f === false) throw new RuntimeException('Không tạo được bản sao lưu; chưa nâng cấp.');
    @chmod($tep, 0600);
    $ghi = static function (string $s) use ($f): void {
        if (fwrite($f, $s) !== strlen($s)) throw new RuntimeException('Ghi bản sao lưu thất bại; chưa nâng cấp.');
    };
    try {
        $pdo->beginTransaction();
        $ghi("-- Sao lưu các bảng EduGPA trước khi nâng cấp; chỉ khôi phục khi cần.\nSET NAMES utf8mb4;\nUSE `" . $tenCsdl . "`;\nSET FOREIGN_KEY_CHECKS=0;\n");
        $cacBang = $pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cacBang as $bang) {
            $ten = '`' . str_replace('`', '``', $bang) . '`';
            $taoBang = $pdo->query('SHOW CREATE TABLE ' . $ten)->fetch(PDO::FETCH_NUM);
            $ghi('DROP TABLE IF EXISTS ' . $ten . ";\n" . $taoBang[1] . ";\n");
            $st = $pdo->query('SELECT * FROM ' . $ten);
            while ($dong = $st->fetch(PDO::FETCH_ASSOC)) {
                $cot = array_map(static fn (string $c): string => '`' . str_replace('`', '``', $c) . '`', array_keys($dong));
                $giaTri = array_map(static fn (mixed $v): string => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($dong));
                $ghi('INSERT INTO ' . $ten . ' (' . implode(',', $cot) . ') VALUES (' . implode(',', $giaTri) . ");\n");
            }
        }
        $ghi("SET FOREIGN_KEY_CHECKS=1;\n");
        $pdo->commit();
        fclose($f);
        return $tep;
    } catch (Throwable $loi) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fclose($f);
        @unlink($tep);
        throw $loi;
    }
}

function caiDatEmail(PDO $pdo): void
{
    $st = $pdo->prepare('SELECT IS_NULLABLE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute(['nguoi_dung', 'email']);
    $nullable = $st->fetchColumn();
    if ($nullable === false) {
        $pdo->exec('ALTER TABLE nguoi_dung ADD COLUMN email VARCHAR(254) NULL AFTER ten_dang_nhap');
        $nullable = 'YES';
    }
    $pdo->exec("UPDATE nguoi_dung SET email=CONCAT(ten_dang_nhap,'@edugpa.local') WHERE email IS NULL OR email=''");
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND NON_UNIQUE=0');
    $st->execute(['nguoi_dung', 'email']);
    if ((int) $st->fetchColumn() === 0) $pdo->exec('ALTER TABLE nguoi_dung ADD UNIQUE KEY uq_nguoi_dung_email (email)');
    if ($nullable === 'YES') $pdo->exec('ALTER TABLE nguoi_dung MODIFY email VARCHAR(254) NOT NULL');
}

function caiDatGopBang(PDO $pdo, string $mau, string $bang, array $khoa, bool $chiKhiTrangTrong = false): void
{
    $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND EXTRA NOT LIKE ? ORDER BY ORDINAL_POSITION');
    $st->execute([$mau, $bang, '%auto_increment%']);
    $cot = $st->fetchAll(PDO::FETCH_COLUMN);
    if ($cot === []) throw new RuntimeException('Thiếu định nghĩa bảng mẫu ' . $bang);
    $trich = static fn (string $c): string => '`' . str_replace('`', '``', $c) . '`';
    $danhSachCot = implode(',', array_map($trich, $cot));
    $giaTri = implode(',', array_map(static fn (string $c): string => 's.' . $trich($c), $cot));
    $soSanh = $chiKhiTrangTrong ? ['mssv'] : $khoa;
    $dk = implode(' AND ', array_map(static fn (string $c): string => 'd.' . $trich($c) . ' <=> s.' . $trich($c), $soSanh));
    $pdo->exec('INSERT INTO `' . $bang . '` (' . $danhSachCot . ') SELECT ' . $giaTri
        . ' FROM `' . $mau . '`.`' . $bang . '` s WHERE NOT EXISTS (SELECT 1 FROM `' . $bang . '` d WHERE ' . $dk . ')');
}

function caiDatGopDuLieu(PDO $pdo, string $mau): array
{
    // Đọc mẫu từ seed trong CSDL riêng: không chép ID cố định lên dữ liệu đang dùng.
    $xungDot = $pdo->query('SELECT s.ten_dang_nhap FROM `' . $mau . '`.nguoi_dung s
        JOIN nguoi_dung d ON d.email=s.email
        WHERE NOT EXISTS (SELECT 1 FROM nguoi_dung x WHERE x.ten_dang_nhap=s.ten_dang_nhap)')->fetchColumn();
    if ($xungDot !== false) throw new RuntimeException('Email tài khoản mẫu ' . $xungDot . ' đã thuộc tài khoản khác; giữ nguyên dữ liệu, cần kiểm tra ánh xạ.');
    $moi = $pdo->query('SELECT s.ten_dang_nhap FROM `' . $mau . '`.nguoi_dung s
        WHERE NOT EXISTS (SELECT 1 FROM nguoi_dung d WHERE d.ten_dang_nhap=s.ten_dang_nhap)')->fetchAll(PDO::FETCH_COLUMN);
    $pdo->exec('INSERT INTO nguoi_dung (email,ten_dang_nhap,mat_khau,ho_ten,vai_tro)
        SELECT s.email,s.ten_dang_nhap,s.mat_khau,s.ho_ten,s.vai_tro FROM `' . $mau . '`.nguoi_dung s
        WHERE NOT EXISTS (SELECT 1 FROM nguoi_dung d WHERE d.ten_dang_nhap=s.ten_dang_nhap) ORDER BY s.id');
    if ((int) $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro='quan_tri'")->fetchColumn() === 0) {
        throw new RuntimeException('Chưa có tài khoản quản trị; không tự đổi quyền tài khoản đã tồn tại.');
    }
    $pdo->exec('INSERT INTO danh_muc (ten,slug) SELECT s.ten,s.slug FROM `' . $mau . '`.danh_muc s
        WHERE NOT EXISTS (SELECT 1 FROM danh_muc d WHERE d.slug=s.slug) ORDER BY s.id');
    $pdo->exec('INSERT INTO mon_hoc (danh_muc_id,ma_mon,ten_mon,so_tin_chi,so_cho,mo_ta,anh)
        SELECT dm.id,s.ma_mon,s.ten_mon,s.so_tin_chi,s.so_cho,s.mo_ta,s.anh
        FROM `' . $mau . '`.mon_hoc s JOIN `' . $mau . '`.danh_muc md ON md.id=s.danh_muc_id
        JOIN danh_muc dm ON dm.slug=md.slug
        WHERE NOT EXISTS (SELECT 1 FROM mon_hoc d WHERE d.ma_mon=s.ma_mon) ORDER BY s.id');
    $pdo->exec('INSERT INTO thanh_vien (mssv,ho_ten,nguoi_dung_id)
        SELECT s.mssv,s.ho_ten,u.id FROM `' . $mau . '`.thanh_vien s
        LEFT JOIN `' . $mau . '`.nguoi_dung mu ON mu.id=s.nguoi_dung_id
        LEFT JOIN nguoi_dung u ON u.ten_dang_nhap=mu.ten_dang_nhap
        WHERE NOT EXISTS (SELECT 1 FROM thanh_vien d WHERE d.mssv=s.mssv)');

    // Máy đã có thành viên Duy: chỉ gắn khi chưa có chủ và tài khoản khớp cả tên/email.
    $pdo->exec("UPDATE thanh_vien tv JOIN nguoi_dung u
        ON u.ten_dang_nhap='duy' AND u.email='duy@example.com'
        SET tv.nguoi_dung_id=u.id WHERE tv.mssv='3120224034' AND tv.nguoi_dung_id IS NULL");

    // Chỉ tạo kế hoạch mẫu cho tài khoản chưa có kế hoạch; không sửa đơn đang có.
    $keHoach = $pdo->query('SELECT s.id,s.trang_thai,s.ghi_chu,u.id AS uid
        FROM `' . $mau . '`.ke_hoach_hoc_tap s JOIN `' . $mau . '`.nguoi_dung mu ON mu.id=s.nguoi_dung_id
        JOIN nguoi_dung u ON u.ten_dang_nhap=mu.ten_dang_nhap
        WHERE NOT EXISTS (SELECT 1 FROM ke_hoach_hoc_tap d WHERE d.nguoi_dung_id=u.id) ORDER BY s.id')->fetchAll();
    $them = $pdo->prepare('INSERT INTO ke_hoach_hoc_tap (nguoi_dung_id,trang_thai,ghi_chu) VALUES (?,?,?)');
    $ct = $pdo->prepare('INSERT INTO chi_tiet_ke_hoach (ke_hoach_id,mon_hoc_id,ma_mon,ten_mon,so_tin_chi,so_luong)
        SELECT ?,m.id,s.ma_mon,s.ten_mon,s.so_tin_chi,s.so_luong FROM `' . $mau . '`.chi_tiet_ke_hoach s
        JOIN `' . $mau . '`.mon_hoc mm ON mm.id=s.mon_hoc_id JOIN mon_hoc m ON m.ma_mon=mm.ma_mon
        WHERE s.ke_hoach_id=?');
    foreach ($keHoach as $k) {
        $them->execute([(int) $k['uid'], $k['trang_thai'], $k['ghi_chu']]);
        $ct->execute([(int) $pdo->lastInsertId(), (int) $k['id']]);
    }
    caiDatGopBang($pdo, $mau, 'tv_uyen_ky_nang', ['mssv','ten']);
    caiDatGopBang($pdo, $mau, 'tv_uyen_thoi_khoa_bieu', ['mssv','ma_mon','thu','tiet_bat_dau']);
    caiDatGopBang($pdo, $mau, 'tv_mon_an', ['mssv','ten_mon','bua']);
    caiDatGopBang($pdo, $mau, 'tv_du_an', ['mssv','ten_du_an']);
    caiDatGopBang($pdo, $mau, 'tv_anh_dai_dien', ['mssv']);
    // Không chèn thêm phiếu/lượt xem/lịch sử mẫu vào dữ liệu thật đã phát sinh.
    foreach (['tv_luot_xem','tv_tra_cuu_mang','tv_lich_su_ott','tv_luu_but'] as $bang) {
        caiDatGopBang($pdo, $mau, $bang, ['mssv'], true);
    }
    $pdo->exec('INSERT INTO tv_binh_chon (mssv,nguoi_dung_id,tuy_chon)
        SELECT s.mssv,u.id,s.tuy_chon FROM `' . $mau . '`.tv_binh_chon s
        JOIN `' . $mau . '`.nguoi_dung mu ON mu.id=s.nguoi_dung_id JOIN nguoi_dung u ON u.ten_dang_nhap=mu.ten_dang_nhap
        WHERE NOT EXISTS (SELECT 1 FROM tv_binh_chon d WHERE d.mssv=s.mssv)');
    return $moi;
}

function caiDatNhom(PDO $pdo, string $tenCsdl = 'ltweb_nhom03', ?string $tepAvatarCu = null): array
{
    if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_]{0,63}\z/D', $tenCsdl) !== 1) {
        throw new InvalidArgumentException('Tên CSDL không hợp lệ.');
    }
    $schema = caiDatDocSql('schema.sql', $tenCsdl);
    preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+(\w+)\s*\([\s\S]*?\n\)[^;]*;/m', $schema, $dinhNghia, PREG_SET_ORDER);
    $batBuoc = array_column($dinhNghia, 1);
    foreach (['nguoi_dung','mon_hoc','gio_hang_nguoi_dung','mon_yeu_thich','tv_uyen_ky_nang','tv_uyen_thoi_khoa_bieu','tv_thoi_tiet_duy','tv_du_an','tv_truc_gpa','tv_truc_quiz'] as $bang) {
        if (!in_array($bang, $batBuoc, true)) throw new RuntimeException('Schema thiếu bảng bắt buộc ' . $bang . '; chưa thay đổi CSDL.');
    }
    $tenKhoa = 'edugpa_setup_' . substr(hash('sha256', $tenCsdl), 0, 40);
    $khoa = $pdo->prepare('SELECT GET_LOCK(?,10)');
    $khoa->execute([$tenKhoa]);
    if ((int) $khoa->fetchColumn() !== 1) throw new RuntimeException('Máy khác đang cài đặt; thử lại sau.');
    $saoLuu = null;
    $mau = 'edugpa_mau_' . bin2hex(random_bytes(8));
    $daTaoMau = false;
    try {
        $st = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');
        $st->execute([$tenCsdl]);
        $cacBang = $st->fetchAll(PDO::FETCH_COLUMN);
        $caiMoi = $cacBang === [];
        if (!$caiMoi) {
            $pdo->exec('USE `' . $tenCsdl . '`');
            $saoLuu = caiDatSaoLuu($pdo, $tenCsdl);
        }
        // Kiểm tra schema/seed trong CSDL mẫu riêng trước khi nâng cấp dữ liệu thật.
        $pdo->exec('CREATE DATABASE `' . $mau . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $daTaoMau = true;
        $pdo->exec('USE `' . $mau . '`');
        $sqlMau = caiDatDocSql('schema.sql', $mau);
        $sqlMau = preg_replace('/^(?:DROP DATABASE IF EXISTS|CREATE DATABASE|USE) [^;]+;\s*$/m', '', $sqlMau);
        $pdo->exec($sqlMau);
        $pdo->beginTransaction();
        $seed = preg_replace('/^USE [^;]+;\s*$/m', '', caiDatDocSql('seed.sql', $mau));
        $pdo->exec($seed);
        $pdo->commit();

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $tenCsdl . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $tenCsdl . '`');
        $cacBang = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $caiMoi = $cacBang === [];
        if ($caiMoi) {
            // Schema gốc phục vụ reset thủ công; bộ cài tuyệt đối không chạy DROP.
            $schemaAnToan = preg_replace('/^DROP DATABASE IF EXISTS [^;]+;\s*$/m', '', $schema);
            $schemaAnToan = str_replace('CREATE DATABASE ' . $tenCsdl . ' ', 'CREATE DATABASE IF NOT EXISTS ' . $tenCsdl . ' ', $schemaAnToan);
            $pdo->exec($schemaAnToan);
            $pdo->beginTransaction();
            $pdo->exec($seed);
            nangCapCaNhanUyen($pdo);
            $pdo->commit();
        } else {
            $saoLuu = caiDatSaoLuu($pdo, $tenCsdl);
            // Chỉ tạo bảng còn thiếu; không dùng seed có ID cố định trên CSDL cũ.
            preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+(\w+)\s*\([\s\S]*?\n\)[^;]*;/m', $schema, $dinhNghia, PREG_SET_ORDER);
            foreach ($dinhNghia as $bang) {
                if (!in_array($bang[1], $cacBang, true)) {
                    $sql = preg_replace('/^CREATE TABLE(?: IF NOT EXISTS)?/', 'CREATE TABLE IF NOT EXISTS', $bang[0]);
                    $pdo->exec($sql);
                }
            }
            caiDatEmail($pdo);
            nangCapKeHoach($pdo);
            nangCapSoCho($pdo);
            nangCapKhoaNgoaiMonHoc($pdo);
            // Bổ sung môn theo mã và danh mục theo slug, không ép ID/ghi đè môn cũ.
            $boSung = caiDatDocSql('bo-sung-30-mon.sql', $tenCsdl);
            // exec chạy tập lệnh SQL cố định; bỏ SELECT báo cáo cuối để không giữ result set.
            $boSung = preg_replace('/^SELECT COUNT\(\*\) AS tong_so_mon FROM mon_hoc;\s*$/m', '', $boSung);
            $pdo->exec($boSung);
            $pdo->exec("INSERT INTO thanh_vien (mssv,ho_ten) VALUES
                ('3120224034','Dương Bảo Duy'),('3120224042','Lê Dương Hoàng Hải'),('3120224064','Phan Văn Hợp')
                ON DUPLICATE KEY UPDATE mssv=VALUES(mssv)");
            nangCapCaNhanUyen($pdo);
        }
        nangCapLienHe($pdo);
        nangCapThoiTietDuy($pdo);
        caiDatEmail($pdo);
        nangCapKeHoach($pdo);
        nangCapSoCho($pdo);
        nangCapKhoaNgoaiMonHoc($pdo);
        preg_match_all('/CREATE INDEX (\w+) ON (\w+) \([^;]+;/m', $schema, $chiMuc, PREG_SET_ORDER);
        $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
        foreach ($chiMuc as $cm) {
            $st->execute([$cm[2], $cm[1]]);
            if ((int) $st->fetchColumn() === 0) $pdo->exec($cm[0]);
        }
        $pdo->beginTransaction();
        $taiKhoanMoi = caiDatGopDuLieu($pdo, $mau);
        $pdo->commit();
        $hai = nangCapCaNhanHai($pdo, null, $tepAvatarCu);
        $hai['tao_tai_khoan'] = $hai['tao_tai_khoan'] || in_array('hai', $taiKhoanMoi, true);
        $hienCo = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff($batBuoc, $hienCo) !== []) throw new RuntimeException('Cài đặt thiếu bảng.');
        return ['cai_moi'=>$caiMoi, 'sao_luu'=>$saoLuu, 'hai'=>$hai, 'tai_khoan_moi'=>$taiKhoanMoi,
            'so_bang'=>count($hienCo), 'so_mon'=>(int) $pdo->query('SELECT COUNT(*) FROM mon_hoc')->fetchColumn()];
    } catch (Throwable $loi) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($saoLuu !== null) fwrite(STDERR, 'Bản sao trước nâng cấp: ' . $saoLuu . PHP_EOL);
        throw $loi;
    } finally {
        // Chỉ dọn CSDL mẫu do chính lượt này tạo, tuyệt đối không DROP CSDL dự án.
        if ($daTaoMau) $pdo->exec('DROP DATABASE `' . $mau . '`');
        $khoa = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $khoa->execute([$tenKhoa]);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
            . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
            getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
            getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_TIMEOUT=>3]);
        $kq = caiDatNhom($pdo, 'ltweb_nhom03', __DIR__ . '/../storage/3120224042_avatar.json');
        echo ($kq['cai_moi'] ? 'Đã tạo CSDL và nạp dữ liệu mẫu.' : 'Đã nâng cấp CSDL, giữ dữ liệu và mật khẩu cũ.') . PHP_EOL;
        if ($kq['sao_luu'] !== null) echo 'Bản sao lưu: ' . $kq['sao_luu'] . PHP_EOL;
        echo 'Số bảng: ' . $kq['so_bang'] . '; số môn: ' . $kq['so_mon'] . PHP_EOL;
        if ($kq['tai_khoan_moi'] !== []) {
            echo 'Tài khoản vừa tạo: ' . implode(', ', $kq['tai_khoan_moi']) . '; mật khẩu mẫu: matkhau123.' . PHP_EOL;
        }
        echo "Tài khoản đã tồn tại giữ nguyên mật khẩu và quyền; chủ trang Hải đã được liên kết.\n";
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Cài đặt chưa hoàn tất: ' . $loi->getMessage() . PHP_EOL);
        exit(1);
    }
}
