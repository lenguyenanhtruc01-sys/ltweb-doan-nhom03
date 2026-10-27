<?php
declare(strict_types=1);

/**
 * API môn học: GET tìm kiếm có phân trang hoặc GET ?id= chi tiết.
 * Thử api.http: id sai 400, không tồn tại 404, phương thức khác GET 405.
 * Model MonHoc tự giới hạn trường JSON bằng JsonSerializable.
 */
require_once __DIR__ . '/../inc/api.php';

use App\Data\KhoMonHoc;
use App\Services\KeHoachHocTap;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        header('Allow: GET');
        traJson(['loi' => 'Chỉ hỗ trợ GET.'], 405);
    }
    if (array_key_exists('id', $_GET)) {
        $id = soNguyenDauVao($_GET['id']);
        if ($id === false) {
            traJson(['loi' => 'ID không hợp lệ.'], 400);
        }
        $mon = (new KhoMonHoc(db()))->timTheoId($id);
        if ($mon === null) {
            traJson(['loi' => 'Không tìm thấy môn học.'], 404);
        }
        traJson(['du_lieu' => $mon]);
    }
    foreach (['q', 'dm', 'tc_toi_da', 'sx', 'trang'] as $key) {
        if (isset($_GET[$key]) && !is_string($_GET[$key])) {
            traJson(['loi' => 'Tham số tìm kiếm không hợp lệ.'], 422);
        }
    }
    $q = layChuoiDauVao($_GET['q'] ?? '');
    if (mb_strlen($q) > 200) {
        traJson(['loi' => 'Từ khóa tối đa 200 ký tự.'], 422);
    }
    $loc = ['q' => $q, 'dm' => soNguyenDauVao($_GET['dm'] ?? '') ?: null,
        'tcToiDa' => soNguyenDauVao($_GET['tc_toi_da'] ?? '', 1, 20) ?: null];
    $sx = layChuoiDauVao($_GET['sx'] ?? 'moi');
    $kho = new KhoMonHoc(db());
    $total = $kho->dem($loc);
    $pages = max(1, (int) ceil($total / 6));
    $page = min(soNguyenDauVao($_GET['trang'] ?? '1') ?: 1, $pages);
    $cart = (new KeHoachHocTap())->layDanhSachChiTiet();
    traJson(['du_lieu' => $kho->trang($loc, $sx, 6, ($page - 1) * 6),
        'tong' => $total, 'trang' => $page, 'so_trang' => $pages,
        'da_them' => array_map('intval', array_column($cart['danh_sach'], 'id'))]);
} catch (Throwable $error) {
    loiApi($error);
}
