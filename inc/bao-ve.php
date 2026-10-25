<?php
/**
 * Tệp: inc/bao-ve.php
 * Chức năng: chặn truy cập trang quản trị khi phiên chưa được xác thực.
 */
require_once __DIR__ . '/config.php';

// Kiểm tra xem người dùng đã đăng nhập hay chưa
$compteAdmin = yeuCauQuyen('quan_tri');
