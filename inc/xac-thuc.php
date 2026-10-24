<?php
/**
 * Tệp: inc/xac-thuc.php
 * Chức năng: Xác thực và Phân quyền (Slide 38 - 42)
 */

// Hàm lấy thông tin người dùng hiện tại (nếu đã đăng nhập)
function nguoiDung(): ?array
{
    static $daTai = false;
    static $nd = null;

    // Trong cùng một request, chỉ truy vấn user một lần.
    if ($daTai) {
        return $nd;
    }
    $daTai = true;

    // Session chỉ giữ uid; thông tin còn lại đọc từ database.
    $uid = $_SESSION['uid'] ?? null;

    if (!is_int($uid) && !(is_string($uid) && ctype_digit($uid))) {
        return null;
    }

    $uid = (int) $uid;

    if ($uid < 1) {
        return null;
    }

    $st = db()->prepare(
        'SELECT id, ten_dang_nhap, email, ho_ten, vai_tro
         FROM nguoi_dung
         WHERE id = ?'
    );
    $st->execute([$uid]);

    $nd = $st->fetch() ?: null;

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