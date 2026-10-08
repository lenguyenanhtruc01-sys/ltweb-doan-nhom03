<?php
// Bắt buộc phải đảm bảo không có bất kỳ khoảng trắng hay ký tự nào trước dòng này

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả các biến session
$_SESSION = array();

// Nếu muốn xóa hoàn toàn session cookie, hãy xóa nó bằng cách thiết lập thời gian hết hạn về quá khứ
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Hủy session trên server
session_destroy();

// Dùng đường dẫn tuyệt đối hoặc tương đối rõ ràng để về trang đăng nhập
header("Location: dang-nhap.php");
exit;