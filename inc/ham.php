<?php
declare(strict_types=1);

/**
 * inc/ham.php
 * Chứa các hàm tiện ích sử dụng chung cho website EduGPA.
 * Hàm e() giúp hiển thị dữ liệu an toàn trong HTML.
 * Hàm dinhDangSo() dùng để định dạng các giá trị số.
 */

// Chuyển ký tự đặc biệt thành thực thể HTML, giúp chống XSS.
function e(mixed $giaTri): string
{
    return htmlspecialchars(
        (string) $giaTri,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Đọc một giá trị đầu vào chỉ khi đó là chuỗi.
 *
 * Dữ liệu GET/POST có thể bị gửi dưới dạng mảng (ví dụ: ?q[]=abc).
 * Không ép mảng sang chuỗi vì PHP sẽ phát Warning; thay vào đó trả về giá trị
 * mặc định để phần kiểm tra dữ liệu của từng trang xử lý an toàn.
 */
function layChuoiDauVao(mixed $giaTri, string $macDinh = ''): string
{
    return is_string($giaTri) ? trim($giaTri) : $macDinh;
}

// Định dạng số theo cách hiển thị thông dụng tại Việt Nam.
function dinhDangSo(int|float $giaTri): string
{
    return number_format($giaTri, 0, ',', '.');
}
