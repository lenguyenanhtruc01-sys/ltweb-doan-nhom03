<?php
declare(strict_types=1);

/**
 * GET doc, POST them/bo mon yeu thich cua tai khoan dang dang nhap.
 * UID chi lay tu session, POST can JSON va token CSRF cua trang.
 * Thu hai tai khoan: du lieu tach rieng, guest nhan 401.
 */
require_once __DIR__ . '/../inc/api.php';

use App\Data\KhoMonHoc;
use App\Data\KhoTrangThaiNguoiDung;

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        traJson(['loi' => 'Chỉ hỗ trợ GET và POST.'], 405);
    }
    $nd = nguoiDung();
    if ($nd === null) traJson(['loi' => 'Vui lòng đăng nhập.'], 401);
    $uid = (int) $nd['id'];
    $kho = new KhoTrangThaiNguoiDung(db());
    if ($method === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $expected = $_SESSION['csrf_gio_hang'] ?? null;
        if (!is_string($token) || !is_string($expected) || !hash_equals($expected, $token)) {
            traJson(['loi' => 'Phiên làm việc không hợp lệ.'], 401);
        }
        try {
            $data = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            traJson(['loi' => 'JSON sai định dạng.'], 400);
        }
        if (!$data instanceof stdClass || !is_bool($data->chon ?? null)) {
            traJson(['loi' => 'Dữ liệu yêu thích không hợp lệ.'], 422);
        }
        $id = soNguyenDauVao($data->id ?? null);
        if ($id === false) traJson(['loi' => 'Mã môn không hợp lệ.'], 422);
        if ((new KhoMonHoc(db()))->timTheoId($id) === null) traJson(['loi' => 'Không tìm thấy môn học.'], 404);
        $kho->datYeuThich($uid, $id, $data->chon);
    }
    traJson(['du_lieu' => $kho->yeuThich($uid)]);
} catch (Throwable $loi) {
    loiApi($loi);
}
