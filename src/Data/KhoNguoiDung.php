<?php
declare(strict_types=1);

/**
 * Truy cập PDO để tạo tài khoản từ dữ liệu đăng ký đã được kiểm tra.
 * Chỉ lưu mật khẩu đã băm, cố định vai trò khách hàng; chuyển lỗi 1062 cho trang xử lý.
 * Thử qua dang-ky.php: tài khoản mới, email/tên trùng và giả mạo vai_tro.
 */
namespace App\Data;

use PDO;

final class KhoNguoiDung
{
    public function __construct(private PDO $pdo) {}

    public function dangKy(string $tenDangNhap, string $email, string $matKhauBam, string $hoTen): int
    {
        $st = $this->pdo->prepare(
            "INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, vai_tro)
             VALUES (?, ?, ?, ?, 'khach_hang')"
        );
        $st->execute([$tenDangNhap, $email, $matKhauBam, $hoTen]);
        return (int) $this->pdo->lastInsertId();
    }
}
