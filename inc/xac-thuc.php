<?php
/**
 * Tệp: inc/xac-thuc.php
 * Chức năng: Xác thực và Phân quyền (Slide 38 - 42)
 */

// Hàm lấy thông tin người dùng hiện tại (nếu đã đăng nhập)
function nguoiDung(): ?array
{
    static $nd = false; // Cache lại để không truy vấn nhiều lần trong 1 request

    if ($nd === false) {
        // Nếu chưa có session uid thì trả về null luôn
        if (empty($_SESSION['uid'])) {
            return $nd = null;
        }

        $st = db()->prepare('SELECT id, ten_dang_nhap, ho_ten, vai_tro FROM nguoi_dung WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $user = $st->fetch();
        
        $nd = $user ? $user : null;
    }
    return $nd;
}

// Hàm bắt buộc phải đăng nhập mới được vào
function yeuCauDangNhap(): array
{
    $nd = nguoiDung();
    if ($nd === null) {
        // Chưa đăng nhập thì đá về trang đăng nhập
        header('Location: dang-nhap.php');
        exit;
    }
    return $nd;
}

// Hàm bắt buộc phải có quyền cụ thể (VD: 'quan_tri')
function yeuCauQuyen(string $vaiTro): array
{
    $nd = yeuCauDangNhap();

    // Nếu vai trò không khớp -> Báo lỗi 403 (Slide 42)
    if ($nd['vai_tro'] !== $vaiTro) {
        http_response_code(403);
        // Bạn có thể require trang 403.php nếu nhóm bạn có sẵn
        exit('<h1>403 Forbidden</h1><p>Bạn không có quyền truy cập trang này.</p>');
    }

    return $nd;
}