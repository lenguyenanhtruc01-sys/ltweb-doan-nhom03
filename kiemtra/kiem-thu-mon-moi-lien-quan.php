<?php
declare(strict_types=1);
/**
 * Kiểm tra JOIN tên danh mục, thứ tự môn mới và loại môn hiện tại khỏi liên quan.
 * Thử danh mục trống/chỉ có một môn, tối đa bốn môn và JSON không thêm cột nội bộ.
 * Chạy CLI trên CSDL tạm riêng: php kiemtra/kiem-thu-mon-moi-lien-quan.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';
function kiemTraMonLienQuan(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_related_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo,$ten);
    $kho = new App\Data\KhoMonHoc($pdo);
    $mon = $kho->timTheoId(1);
    $dm = $pdo->query('SELECT ten FROM danh_muc WHERE id=1')->fetchColumn();
    kiemTraMonLienQuan($mon !== null && $mon->danhMucId === 1 && $mon->tenDanhMuc === $dm, 'JOIN sai tên danh mục.');
    kiemTraMonLienQuan($kho->timTheoId(99999999) === null, 'ID không có phải trả null.');
    $lienQuan = $kho->cungDanhMuc(1,1);
    kiemTraMonLienQuan(count($lienQuan) === 4, 'Không lấy đủ bốn môn cùng danh mục.');
    foreach ($lienQuan as $m) {
        kiemTraMonLienQuan($m->danhMucId === 1 && (int) $m->id !== 1, 'Lẫn danh mục hoặc môn hiện tại.');
    }
    kiemTraMonLienQuan($kho->cungDanhMuc(5,1) === [], 'Danh mục rỗng không trả mảng rỗng.');
    $st = $pdo->prepare('INSERT INTO mon_hoc (danh_muc_id,ma_mon,ten_mon,so_tin_chi,ngay_tao) VALUES (5,?,?,3,?)');
    $st->execute(['AUDITNEW','Môn có ngày tạo mới','2099-01-01 00:00:00']);
    $idMoi = (int) $pdo->lastInsertId();
    kiemTraMonLienQuan($kho->cungDanhMuc(5,$idMoi) === [], 'Một môn tự xuất hiện trong liên quan.');
    $st->execute(['AUDITOLD','Môn ID lớn nhưng ngày tạo cũ','2000-01-01 00:00:00']);
    $idCu = (int) $pdo->lastInsertId();
    $moi = $kho->moiNhat();
    kiemTraMonLienQuan(count($moi) === 4 && (int) $moi[0]->id === $idMoi
        && !in_array($idCu,array_column($moi,'id')), 'Môn mới không sắp theo ngày tạo.');
    $itMon = $kho->cungDanhMuc(5,$idMoi);
    kiemTraMonLienQuan(count($itMon) === 1 && (int) $itMon[0]->id === $idCu, 'Danh mục ít môn bị thêm dữ liệu giả.');
    $json = $mon->jsonSerialize();
    kiemTraMonLienQuan(!isset($json['danhMucId']) && !isset($json['tenDanhMuc'])
        && !isset($json['danh_muc_id']), 'Thay đổi ngoài ý muốn trường JSON công khai.');
    echo "PASS tên danh mục bằng JOIN, liên quan đúng danh mục/không trùng môn hiện tại.\n";
    echo "PASS tối đa bốn môn, danh mục rỗng/ít môn, mới nhất theo ngày tạo và JSON giữ nguyên.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
    echo "Đã xóa CSDL kiểm thử riêng.\n";
}
