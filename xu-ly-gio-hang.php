<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Services\KeHoachHocTap;

$keHoach = new KeHoachHocTap();
$hanhDong = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($hanhDong) {
        case 'them':
            $id = $_POST['id'] ?? 0;
            $soLuong = $_POST['so_luong'] ?? 1;
            $keHoach->them($id, $soLuong);
            break;

        case 'cap_nhat':
            $danhSachSoLuong = $_POST['so_luong'] ?? [];
            foreach ($danhSachSoLuong as $id => $sl) {
                $keHoach->capNhat($id, $sl);
            }
            break;

        case 'xoa':
            $id = $_POST['id'] ?? 0;
            $keHoach->xoa($id);
            break;

        case 'xoa_tat_ca':
            $keHoach->xoaTatCa();
            break;
    }
}

// Áp dụng mô hình PRG: Chuyển hướng (Redirect) về trang hiển thị giỏ hàng
header('Location: gio-hang.php');
exit;