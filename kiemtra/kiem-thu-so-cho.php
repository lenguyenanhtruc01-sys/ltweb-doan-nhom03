<?php
declare(strict_types=1);

/**
 * Kiểm thử giới hạn chỗ và rollback trên CSDL ngẫu nhiên, không sửa CSDL thật.
 * Chạy CLI: php kiemtra/kiem-thu-so-cho.php (cần quyền CREATE/DROP DATABASE).
 * Thử migration lặp, thiếu môn thứ hai, snapshot và hai tiến trình tranh chỗ cuối.
 * Luôn xóa CSDL kiểm thử trong finally; không tạo tài khoản hoặc kế hoạch thật.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy bằng PHP CLI.');
}
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../database/nang-cap-so-cho.php';

use App\Data\KhoKeHoach;
use App\Data\MonKhongDuChoException;

function kiemTra(bool $dung, string $moTa): void
{
    if (!$dung) throw new RuntimeException($moTa);
}

$pdo = db();
if (($argv[1] ?? '') === '--worker') {
    $ten = $argv[2] ?? '';
    if (!preg_match('/\Aedugpa_cho_[a-f0-9]{12}\z/D', $ten)) exit(2);
    $pdo->exec('USE `' . $ten . '`');
    try {
        (new KhoKeHoach($pdo))->taoKeHoach((int) ($argv[3] ?? 0), [30 => 1]);
        echo 'THANH_CONG';
    } catch (MonKhongDuChoException) {
        echo 'THIEU_CHO';
    }
    exit;
}

$ten = 'edugpa_cho_' . bin2hex(random_bytes(6));
$tienTrinh = [];
try {
    foreach (['schema.sql', 'seed.sql'] as $tep) {
        $sql = file_get_contents(__DIR__ . '/../database/' . $tep);
        if ($sql === false) throw new RuntimeException('Không đọc được SQL.');
        $pdo->exec(str_replace('ltweb_nhom03', $ten, $sql));
    }
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=30')->fetchColumn() === 1, 'Seed thiếu môn còn 1 chỗ.');
    kiemTra(!nangCapSoCho($pdo), 'Migration ghi đè cột đã có.');
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=30')->fetchColumn() === 1, 'Migration làm đổi số chỗ.');
    // Mô phỏng CSDL cũ; so sánh toàn bộ dòng để phát hiện mất dữ liệu.
    $pdo->exec('ALTER TABLE mon_hoc DROP COLUMN so_cho');
    $truoc = $pdo->query('SELECT * FROM mon_hoc ORDER BY id')->fetchAll();
    kiemTra(nangCapSoCho($pdo), 'Không bổ sung được cột cũ.');
    $sau = $pdo->query('SELECT * FROM mon_hoc ORDER BY id')->fetchAll();
    foreach ($sau as &$dong) {
        kiemTra((int) $dong['so_cho'] === 50, 'Giá trị mặc định sai.');
        unset($dong['so_cho']);
    }
    unset($dong);
    kiemTra($truoc === $sau && !nangCapSoCho($pdo), 'Migration không giữ dữ liệu hoặc không lặp được.');
    echo "PASS schema/seed và migration không mất dữ liệu, chạy lại giữ số chỗ.\n";

    $pdo->exec('UPDATE mon_hoc SET so_cho=2 WHERE id=1');
    $pdo->exec('UPDATE mon_hoc SET so_cho=0 WHERE id=2');
    $pdo->exec('INSERT INTO gio_hang_nguoi_dung VALUES (2,1,1),(2,2,1)');
    $bang = ['ke_hoach_hoc_tap', 'chi_tiet_ke_hoach', 'gio_hang_nguoi_dung'];
    $anhTruoc = [];
    foreach ($bang as $b) $anhTruoc[$b] = $pdo->query('SELECT * FROM ' . $b)->fetchAll();
    try {
        (new KhoKeHoach($pdo))->taoKeHoach(2, [1=>1, 2=>1]);
        throw new RuntimeException('Đã cho gửi khi môn thứ hai hết chỗ.');
    } catch (MonKhongDuChoException) {}
    foreach ($bang as $b) kiemTra($anhTruoc[$b] === $pdo->query('SELECT * FROM ' . $b)->fetchAll(), 'Rollback sai bảng ' . $b);
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=1')->fetchColumn() === 2, 'Môn đầu không được hoàn sau rollback.');
    echo "PASS môn thứ hai hết chỗ: rollback kế hoạch, chi tiết, số chỗ; giữ giỏ.\n";

    $pdo->exec('UPDATE mon_hoc SET so_cho=1 WHERE id=2');
    $id = (new KhoKeHoach($pdo))->taoKeHoach(2, [1=>1, 2=>1]);
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=1')->fetchColumn() === 1, 'Trừ chỗ môn 1 sai.');
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=2')->fetchColumn() === 0, 'Trừ chỗ môn 2 sai.');
    kiemTra((int) $pdo->query('SELECT COUNT(*) FROM gio_hang_nguoi_dung WHERE nguoi_dung_id=2')->fetchColumn() === 0, 'Giỏ gửi thành công chưa xóa.');
    $pdo->exec("UPDATE mon_hoc SET ten_mon='Đổi tên sau khi gửi', so_tin_chi=5 WHERE id=1");
    $keHoach = (new KhoKeHoach($pdo))->chiTiet($id, 2, false);
    kiemTra($keHoach !== null && count($keHoach['chi_tiet']) === 2, 'Thiếu chi tiết.');
    $ct = array_column($keHoach['chi_tiet'], null, 'mon_hoc_id');
    kiemTra($ct[1]['ten_mon'] === 'Giải tích 1' && (int) $ct[1]['so_tin_chi'] === 4, 'Snapshot bị thay đổi.');
    echo "PASS gửi hai môn thành công, trừ chỗ và giữ snapshot.\n";

    $pdo->exec('UPDATE mon_hoc SET so_cho=1 WHERE id=30');
    $demTruoc = (int) $pdo->query('SELECT COUNT(*) FROM ke_hoach_hoc_tap')->fetchColumn();
    foreach ([2,3] as $uid) {
        $ong = [];
        $p = proc_open([PHP_BINARY, __FILE__, '--worker', $ten, (string) $uid],
            [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $ong, null, null, ['bypass_shell'=>true]);
        if (!is_resource($p)) throw new RuntimeException('Không khởi động được tiến trình kiểm thử.');
        fclose($ong[0]);
        $tienTrinh[] = [$p, $ong];
    }
    $ketQua = [];
    foreach ($tienTrinh as [$p, $ong]) {
        $ketQua[] = stream_get_contents($ong[1]);
        $loi = stream_get_contents($ong[2]);
        fclose($ong[1]); fclose($ong[2]);
        kiemTra(proc_close($p) === 0 && $loi === '', 'Tiến trình lỗi: ' . $loi);
    }
    $tienTrinh = [];
    sort($ketQua);
    kiemTra($ketQua === ['THANH_CONG', 'THIEU_CHO'], 'Hai người cùng lấy được chỗ cuối.');
    kiemTra((int) $pdo->query('SELECT so_cho FROM mon_hoc WHERE id=30')->fetchColumn() === 0, 'Chỗ cuối bị trừ sai.');
    kiemTra((int) $pdo->query('SELECT COUNT(*) FROM ke_hoach_hoc_tap')->fetchColumn() === $demTruoc+1, 'Sinh thừa kế hoạch.');
    echo "PASS hai tiến trình tranh chỗ cuối: chỉ một thành công, không âm chỗ.\n";
} finally {
    foreach ($tienTrinh as [$p, $ong]) {
        if (is_resource($p)) { proc_terminate($p); proc_close($p); }
    }
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã xóa CSDL kiểm thử riêng.\n";
}
