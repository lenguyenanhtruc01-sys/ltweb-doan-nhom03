<?php
declare(strict_types=1);

/**
 * Một lệnh tạo CSDL mới hoặc nâng cấp CSDL nhóm đang có, không DROP DATABASE.
 * Chạy tại gốc dự án: php database/cai-dat.php; MySQL phải đang chạy.
 * Bản mới nạp schema/seed; bản cũ sao lưu trước và giữ tài khoản, mật khẩu, dữ liệu.
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

function caiDatDocSql(string $tep, string $tenCsdl): string
{
    $sql = file_get_contents(__DIR__ . '/' . $tep);
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

function caiDatNhom(PDO $pdo, string $tenCsdl = 'ltweb_nhom03', ?string $tepAvatarCu = null): array
{
    // Tên CSDL là cấu trúc SQL nên kiểm tra chặt trước khi trích dẫn bằng backtick.
    if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_]{0,63}\z/D', $tenCsdl) !== 1) {
        throw new InvalidArgumentException('Tên CSDL không hợp lệ.');
    }
    $schema = caiDatDocSql('schema.sql', $tenCsdl);
    $seed = caiDatDocSql('seed.sql', $tenCsdl);
    $khoa = $pdo->prepare('SELECT GET_LOCK(?,10)');
    $tenKhoa = 'edugpa_setup_' . substr(hash('sha256', $tenCsdl), 0, 40);
    $khoa->execute([$tenKhoa]);
    if ((int) $khoa->fetchColumn() !== 1) throw new RuntimeException('Máy khác đang cài đặt; thử lại sau.');
    $saoLuu = null;
    try {
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
        }
        $hai = nangCapCaNhanHai($pdo, null, $tepAvatarCu);
        return ['cai_moi'=>$caiMoi, 'sao_luu'=>$saoLuu, 'hai'=>$hai,
            'so_mon'=>(int) $pdo->query('SELECT COUNT(*) FROM mon_hoc')->fetchColumn()];
    } catch (Throwable $loi) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($saoLuu !== null) fwrite(STDERR, 'Bản sao trước nâng cấp: ' . $saoLuu . PHP_EOL);
        throw $loi;
    } finally {
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
        echo 'Số môn: ' . $kq['so_mon'] . PHP_EOL;
        if ($kq['cai_moi']) echo "Tài khoản mẫu: admin, an, binh, hai; mật khẩu: matkhau123.\n";
        elseif ($kq['hai']['tao_tai_khoan']) echo "Đã tạo chủ trang Hải: hai / matkhau123.\n";
        else echo "Tài khoản chủ trang Hải đã có, giữ nguyên mật khẩu hiện tại.\n";
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Cài đặt chưa hoàn tất: ' . $loi->getMessage() . PHP_EOL);
        exit(1);
    }
}
