<?php
declare(strict_types=1);


/**
 * Sinh và kiểm tra token CSRF lưu trong session cho các form POST.
 * Trang gọi phải kiểm tra kiểu chuỗi trước khi truyền token nhận từ người dùng.
 * Thử form có token đúng/sai/thiếu và _csrf[] tại gửi kế hoạch hoặc đăng xuất.
 */
function csrfToken(): string {
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrfKiemTra(?string $token): bool {
    return is_string($token) && $token !== ''
        && isset($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
}