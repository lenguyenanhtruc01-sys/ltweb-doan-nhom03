<?php
declare(strict_types=1);
/**
 * Kiểm tra kết quả GPA và điểm quiz được lưu MySQL, tách theo người xem.
 * Thử reset có phạm vi, khóa ngoại, đáp án không hợp lệ và nâng cấp giữ dữ liệu.
 * Chỉ tạo/xóa CSDL ngẫu nhiên: php kiemtra/kiem-thu-gpa-quiz-truc.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../database/cai-dat.php';
require_once __DIR__ . '/../vendor/autoload.php';
function kiemTraTruc(bool $dung, string $loi): void
{
    if (!$dung) throw new RuntimeException($loi);
}
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
    . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8mb4',
    getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
    getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);
$ten = 'edugpa_truc_' . bin2hex(random_bytes(8));
try {
    caiDatNhom($pdo, $ten);
    $kho = new App\Data\KhoTrangCaNhanTruc($pdo);
    $a = hash('sha256', 'guest:a'); $b = hash('sha256', 'guest:b');
    $m = '3120224159';
    $kq = ['gpa_hien_tai'=>3.0,'gpa_muc_tieu'=>3.2,'tin_chi_da_hoc'=>60,'tin_chi_hoc_ky'=>20,'hoc_phi_tin_chi'=>500000,'gpa_can_dat'=>3.8,'tong_hoc_phi'=>10000000,'co_the_dat'=>true];
    kiemTraTruc($kho->layGpa($m,$a) === null, 'Có kết quả mẫu giả.');
    $kho->luuGpa($m,$a,$kq);
    kiemTraTruc($kho->layGpa($m,$a) == $kq && $kho->layGpa($m,$b) === null, 'GPA bị mất hoặc lẫn người xem.');
    $kho->luuQuiz($m,$a,'HTML?','b','b');
    $kho->luuQuiz($m,$a,'CSS?','a','c');
    $kho->luuQuiz($m,$b,'PHP?','a','a');
    kiemTraTruc($kho->thongKeQuiz($m,$a) === ['total'=>2,'score'=>1], 'Điểm thống kê sai.');
    kiemTraTruc($kho->layQuizGanNhat($m,$a)['correct_answer'] === 'c', 'Mất đáp án lần trước.');
    caiDatNhom($pdo,$ten);
    kiemTraTruc($kho->layGpa($m,$a) == $kq && $kho->thongKeQuiz($m,$a)['total'] === 2, 'Nâng cấp làm mất kết quả.');
    $kho->xoaQuiz($m,$a);
    kiemTraTruc($kho->thongKeQuiz($m,$a)['total'] === 0 && $kho->thongKeQuiz($m,$b)['total'] === 1, 'Reset ảnh hưởng người khác.');
    try { $kho->luuQuiz($m,$a,'Sai','x','a'); throw new RuntimeException('Đã nhận đáp án sai.'); }
    catch (InvalidArgumentException $e) {}
    try { $kho->luuGpa('9999999999',$a,$kq); throw new RuntimeException('Đã nhận MSSV không tồn tại.'); }
    catch (PDOException $e) { kiemTraTruc($e->getCode() === '23000','FK lỗi khác dự kiến.'); }
    echo "PASS GPA/quiz MySQL: lưu/đọc, tính điểm, tách người xem, reset, đáp án, FK và nâng cấp giữ dữ liệu.\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $ten . '`');
}
