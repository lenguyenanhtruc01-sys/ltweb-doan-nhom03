<?php
declare(strict_types=1);
/**
 * Đếm truy vấn của khối đã xem và kiểm tra thứ tự, ID trùng/lỗi/không tồn tại.
 * Chỉ SELECT trên CSDL hiện tại; không tạo/sửa/xóa dữ liệu.
 * Chạy CLI: php kiemtra/kiem-thu-da-xem.php; cần ít nhất 5 môn học.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../vendor/autoload.php';

class PdoDemDaXem extends PDO
{
    public int $soCau = 0;
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->soCau++;
        return parent::prepare($query, $options);
    }
}
$pdo = new PdoDemDaXem('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=ltweb_nhom03;charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ids = array_map('intval', $pdo->query('SELECT id FROM mon_hoc ORDER BY id LIMIT 5')->fetchAll(PDO::FETCH_COLUMN));
if (count($ids) < 5) throw new RuntimeException('Cần ít nhất 5 môn để thử giới hạn kết quả.');
[$a,$b,$c,$d,$e] = $ids;
$kho = new App\Data\KhoMonHoc($pdo);
$cases = [
    ['cookie hợp lệ', "$d,$b,$a,$c,$e", 4, null, [$d,$b,$a,$c], 1],
    ['trùng/rác/ID không tồn tại', "9999999999,$b,rac,$b,-1,0,$d,$a,$c", 4, null, [$b,$d,$a,$c], 1],
    ['loại môn hiện tại', "$b,$a,$c,$d,$e", 3, $a, [$b,$c,$d], 1],
    ['cookie rỗng', '', 4, null, [], 0],
    ['cookie dạng mảng', [$a,$b], 4, null, [], 0],
    ['không có ID hợp lệ', 'abc,-1,0', 4, null, [], 0],
    ['chỉ có ID đã xóa', '9999999999', 4, null, [], 1],
    ['giới hạn 20 mục đầu', implode(',', array_fill(0,20,'abc')) . ",$a", 4, null, [], 0],
];
foreach ($cases as [$ten,$cookie,$limit,$exclude,$mongDoi,$soCau]) {
    $pdo->soCau = 0;
    $thucTe = array_map(static fn ($mon): int => (int) $mon->id, $kho->daXemGanDay($cookie,$limit,$exclude));
    if ($thucTe !== $mongDoi || $pdo->soCau !== $soCau) {
        throw new RuntimeException($ten . ': sai thứ tự/kết quả hoặc số câu SQL.');
    }
    echo 'PASS ' . $ten . ': ' . $pdo->soCau . " truy vấn.\n";
}
