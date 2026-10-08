<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Services\KeHoachHocTap;

$keHoach = new KeHoachHocTap();

// Đồng bộ bắt cả 'hanh_dong' (từ form HTML) lẫn 'action' (phòng hờ)
$hanhDong = $_POST['hanh_dong'] ?? $_GET['hanh_dong'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($hanhDong) {
        case 'them':
            $id = $_POST['id'] ?? 0;
            $soLuong = $_POST['so_luong'] ?? 1;
            $keHoach->them($id, $soLuong);
            break;

        case 'cap_nhat':
            $id = $_POST['id'] ?? 0;
            $soLuong = $_POST['so_luong'] ?? 1;
            $keHoach->capNhat($id, $soLuong);
            break;

        case 'xoa':
            $id = $_POST['id'] ?? 0;
            $keHoach->xoa($id);
            break;

        // Đồng bộ khớp với 'xoa_het' từ nút trong giao diện giỏ hàng
        case 'xoa_het':
            $keHoach->xoaTatCa();
            break;
    }
}

// Áp dụng mô hình PRG: Chuyển hướng (Redirect) về trang hiển thị giỏ hàng
header('Location: gio-hang.php');
exit;