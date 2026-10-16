<?php
/**
 * Tệp: inc/tai-khoan.php
 * Chức năng: trả về cấu hình tài khoản quản trị dùng cho môi trường phát triển.
 */

return [
    'admin' => [
        'username' => 'admin',
        // Tự động băm mật khẩu '123456' bằng thuật toán chuẩn an toàn
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'ho_ten' => 'Quản trị viên EduGPA'
    ]
];
