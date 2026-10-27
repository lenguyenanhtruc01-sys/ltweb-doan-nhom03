<?php
declare(strict_types=1);

/**
 * GET xem giỏ và nhận CSRF token; POST JSON {id, sl} thêm môn (1–5).
 * Thử api.http: 400/401/404/405/422; lỗi không làm đổi giỏ session.
 * Giỏ khách dùng cookie session độc lập với đăng nhập quản trị.
 */
require_once __DIR__ . '/../inc/api.php';

use App\Data\KhoMonHoc;
use App\Services\KeHoachHocTap;

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        traJson(['loi' => 'Chỉ hỗ trợ GET và POST.'], 405);
    }
    if ($method === 'GET') {
        $cart = (new KeHoachHocTap())->layDanhSachChiTiet();
        traJson(['so_mon' => $cart['tong_so_mon'], 'du_lieu' => $cart['danh_sach'],
            'csrf' => tokenGioHang()]);
    }
    try {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            throw new RuntimeException('Không đọc được thân request.');
        }
        $data = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        traJson(['loi' => 'JSON sai định dạng.'], 400);
    }
    if (!$data instanceof stdClass) {
        traJson(['loi' => 'Thân JSON phải là đối tượng.'], 422);
    }
    $id = soNguyenDauVao($data->id ?? null);
    $quantity = soNguyenDauVao($data->sl ?? $data->so_luong ?? null, 1, 5);
    if ($id === false || $quantity === false) {
        traJson(['loi' => 'ID phải dương, số lượng là số nguyên từ 1 đến 5.'], 422);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $expected = $_SESSION['csrf_gio_hang'] ?? null;
    if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
        traJson(['loi' => 'Phiên giỏ hàng không hợp lệ. Tải lại trang để thử lại.'], 401);
    }
    $mon = (new KhoMonHoc(db()))->timTheoId($id);
    if ($mon === null) {
        traJson(['loi' => 'Không tìm thấy môn học.'], 404);
    }
    if ($quantity > $mon->soCho) {
        traJson(['loi' => 'Môn học không còn đủ chỗ đăng ký.'], 422);
    }
    // Khôi phục trạng thái cũ nếu đọc CSDL/serialize phản hồi gặp lỗi sau khi thêm.
    $oldCart = $_SESSION['ke_hoach_dang_ky'] ?? [];
    try {
        $service = new KeHoachHocTap();
        if (!$service->them($id, $quantity)) {
            traJson(['loi' => 'Không thể thêm môn học.'], 422);
        }
        $cart = $service->layDanhSachChiTiet();
        json_encode($cart, JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        $_SESSION['ke_hoach_dang_ky'] = $oldCart;
        throw $error;
    }
    traJson(['so_mon' => $cart['tong_so_mon'], 'du_lieu' => $cart['danh_sach'],
        'thong_bao' => 'Môn học đã được thêm vào kế hoạch học tập.']);
} catch (Throwable $error) {
    loiApi($error);
}
