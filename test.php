<?php
// Nạp Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

use App\Data\KhoMonHoc;
use App\Services\KeHoachHocTap;

echo "<h2>Kiểm tra toàn bộ hệ thống EduGPA</h2>";
$loi = 0;

// 1. Kiểm tra file cấu hình tài khoản quản trị
$fileTaiKhoan = __DIR__ . '/inc/tai-khoan.php';
if (file_exists($fileTaiKhoan)) {
    $taiKhoan = require$fileTaiKhoan;
    if (isset($taiKhoan['admin']) && password_verify('123456',$taiKhoan['admin']['password'])) {
        echo "<p style='color: green;'>[OK] Tài khoản quản trị và mật khẩu băm (`password_verify`) hoạt động chính xác.</p>";
    } else {
        echo "<p style='color: red;'>[LỖI] Tài khoản admin hoặc mã băm mật khẩu trong `inc/tai-khoan.php` không khớp.</p>";
        $loi++;
    }
} else {
    echo "<p style='color: red;'>[LỖI] Không tìm thấy file `inc/tai-khoan.php`.</p>";
    $loi++;
}

// 2. Kiểm tra file bảo vệ trang quản trị
$fileBaoVe = __DIR__ . '/inc/bao-ve.php';
if (file_exists($fileBaoVe)) {
    echo "<p style='color: green;'>[OK] File bảo vệ `inc/bao-ve.php` tồn tại.</p>";
} else {
    echo "<p style='color: red;'>[LỖI] Thiếu file `inc/bao-ve.php`.</p>";
    $loi++;
}

// 3. Kiểm tra KhoMonHoc và dữ liệu JSON
$kho = new KhoMonHoc();
$danhSach =$kho->tatCa();
if (count($danhSach) > 0) {
    echo "<p style='color: green;'>[OK] Đọc dữ liệu JSON thành công (Tìm thấy " . count($danhSach) . " môn học).</p>";
} else {
    echo "<p style='color: red;'>[LỖI] Không đọc được dữ liệu môn học từ file JSON.</p>";
    $loi++;
}

// 4. Kiểm tra Class KeHoachHocTap (Giỏ hàng / Đăng ký môn học)
try {
    $keHoach = new KeHoachHocTap();
    // Thử validate min_range, max_range và kiểm tra ID tồn tại
    $idTest = count($danhSach) > 0 ?$danhSach[0]->id : 1;
    $ketQuaThem = $keHoach->them($idTest, 1);
    
    if ($ketQuaThem) {
        echo "<p style='color: green;'>[OK] Lớp `KeHoachHocTap` (Session) thêm môn học và validate thành công.</p>";
    } else {
        echo "<p style='color: orange;'>[CẢNH BÁO] Thêm môn học vào session cần kiểm tra lại ID thực tế.</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>[LỖI] Lớp `KeHoachHocTap` gặp ngoại lệ: " . $e->getMessage() . "</p>";
    $loi++;
}

echo "<hr>";
if ($loi === 0) {
    echo "<h3 style='color: green;'>TẤT CẢ CÁC KIỂM TRA ĐÃ HOÀN TẤT VÀ HOẠT ĐỘNG HOÀN HẢO!</h3>";
} else {
    echo "<h3 style='color: red;'>Có $loi lỗi cần khắc phục như trên.</h3>";
}
?>