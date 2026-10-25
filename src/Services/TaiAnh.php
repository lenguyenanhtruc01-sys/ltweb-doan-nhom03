<?php
declare(strict_types=1);

/**
 * Lưu ảnh an toàn với tên ngẫu nhiên, không tin tên từ trình duyệt.
 * Tự tạo thư mục và báo lỗi có kiểm soát khi tệp hoặc quyền ghi không hợp lệ.
 * Thử: ảnh thật tên .php phải lưu đúng MIME; ảnh giả hoặc quá 2MB bị từ chối.
 */
namespace App\Services;

use RuntimeException;

class TaiAnh
{
    public function __construct(private string $thuMuc) {}

    public function luu(array $tep): ?string
    {
        $loi = $tep['error'] ?? null;
        if (!is_int($loi)) {
            throw new RuntimeException('Dữ liệu ảnh tải lên không hợp lệ.');
        }
        if ($loi === UPLOAD_ERR_NO_FILE) return null;
        if ($loi === UPLOAD_ERR_INI_SIZE || $loi === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('Kích thước ảnh không được vượt quá 2MB.');
        }
        if ($loi !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Có lỗi trong quá trình tải ảnh lên.');
        }
        $tam = $tep['tmp_name'] ?? null;
        if (!is_string($tam) || !is_uploaded_file($tam)) {
            throw new RuntimeException('Tệp tải lên không hợp lệ.');
        }
        $kichThuoc = filesize($tam);
        if ($kichThuoc === false || $kichThuoc > 2 * 1024 * 1024) {
            throw new RuntimeException('Kích thước ảnh không được vượt quá 2MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tam);
        $duoi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        // Kiểm tra ảnh và chuyển lỗi thành thông báo biểu mẫu.
        $anh = isset($duoi[$mime]) ? @getimagesize($tam) : false;
        if (!is_array($anh) || ($anh['mime'] ?? '') !== $mime) {
            throw new RuntimeException('Hệ thống chỉ chấp nhận ảnh JPG, PNG hoặc WebP hợp lệ.');
        }
        if (!is_dir($this->thuMuc) && !@mkdir($this->thuMuc, 0775, true) && !is_dir($this->thuMuc)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh. Vui lòng kiểm tra quyền ghi.');
        }
        $ten = 'anh_' . bin2hex(random_bytes(16)) . '.' . $duoi[$mime];
        if (!@move_uploaded_file($tam, $this->thuMuc . '/' . $ten)) {
            throw new RuntimeException('Không thể lưu ảnh. Vui lòng kiểm tra quyền ghi thư mục uploads.');
        }
        return $ten;
    }
}
