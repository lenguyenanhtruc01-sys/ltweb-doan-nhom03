<?php
declare(strict_types=1);

/**
 * inc/ham.php
 * Chứa các hàm tiện ích sử dụng chung cho website EduGPA.
 * Hàm e() giúp hiển thị dữ liệu an toàn trong HTML.
 * Hàm dinhDangSo() dùng để định dạng các giá trị số.
 * Thử: e() với chuỗi chứa thẻ HTML; layChuoiDauVao() với mảng trả giá trị mặc định.
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

// Giữ nguyên khoảng trắng, đặc biệt với mật khẩu.
function chuoi(mixed $giaTri): string
{
    return is_string($giaTri) ? $giaTri : '';
}

// Chỉ nhận số nguyên hoặc chuỗi số; JSON boolean/float và đầu vào mảng bị từ chối.
function soNguyenDauVao(mixed $giaTri, int $min = 1, int $max = PHP_INT_MAX): int|false
{
    if (!is_int($giaTri) && !is_string($giaTri)) return false;
    return filter_var($giaTri, FILTER_VALIDATE_INT, ['options' => ['min_range'=>$min, 'max_range'=>$max]]);
}

// Định dạng số theo cách hiển thị thông dụng tại Việt Nam.
function dinhDangSo(int|float $giaTri): string
{
    return number_format($giaTri, 0, ',', '.');
}

/**
 * Chương 7 (slide 32, gợi ý 6): một sự kiện bảo mật = một dòng trong logs/bao-mat.log.
 * Ba sự kiện: dang_nhap_sai, cam_truy_cap (yeuCauQuyen), csrf_sai (inc/config.php).
 * Không bao giờ ghi mật khẩu, mã phiên, mã CSRF vào $chiTiet.
 */
function ghiLogBaoMat(string $suKien, array $chiTiet = []): void
{
    $chiTiet += [
        'ip'  => $_SERVER['REMOTE_ADDR'] ?? '-',
        'uid' => $_SESSION['uid'] ?? null,
    ];
    $json = json_encode(
        $chiTiet,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    $dong = date('Y-m-d H:i:s') . " $suKien $json";
    error_log($dong . PHP_EOL, 3, __DIR__ . '/../logs/bao-mat.log');
}

// Seed chỉ lưu tên tệp; ảnh quản trị tải lên đã có tiền tố uploads/.
function duongDanAnhMonHoc(?string $anh): string
{
    $anh = trim($anh ?? '');
    return $anh === '' || preg_match('~^(?:https?://|/|images/|uploads/)~i', $anh)
        ? $anh : 'images/' . $anh;
}
