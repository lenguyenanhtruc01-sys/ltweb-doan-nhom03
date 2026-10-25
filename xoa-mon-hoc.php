<?php
declare(strict_types=1);

/**
 * Xóa môn học bằng POST, kiểm tra quyền quản trị và CSRF tại máy chủ.
 * Bắt khóa ngoại 1451 để giữ môn đã có kế hoạch; chuyển hướng kèm flash.
 * Thử GET trả 405, khách bị chặn và POST xóa môn MAT101 bị báo đang sử dụng.
 */
require_once __DIR__ . '/inc/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Chỉ hỗ trợ POST.');
}
yeuCauQuyen('quan_tri');
$token = $_POST['_csrf'] ?? null;
$idRaw = $_POST['id'] ?? null;
$id = is_string($idRaw) ? filter_var($idRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : false;
if (!is_string($token) || !csrfKiemTra($token) || $id === false) {
    $_SESSION['flash_error'] = 'Dữ liệu hoặc phiên làm việc không hợp lệ.';
} else {
    try {
        $daXoa = (new \App\Data\KhoQuanTriMonHoc(db()))->xoa($id);
        $_SESSION[$daXoa ? 'flash_success' : 'flash_error'] = $daXoa ? 'Đã xóa môn học.' : 'Môn học không tồn tại.';
    } catch (PDOException $loi) {
        if ((int) ($loi->errorInfo[1] ?? 0) !== 1451) throw $loi;
        $_SESSION['flash_error'] = 'Không thể xóa: môn học đã có trong kế hoạch học tập.';
    }
}
header('Location: quan-tri-mon-hoc.php', true, 303);
exit;
