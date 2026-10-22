<?php
declare(strict_types=1);

/**
 * Lưu tên ảnh đại diện trong storage, không chấp nhận đường dẫn tùy ý.
 * Ảnh thực tế được lưu trong uploads bằng tên do server sinh ra.
 */
namespace App\Data;

class KhoAnhDaiDien
{
    public function __construct(private string $tep) {}

    public function lay(): ?string
    {
        if (!is_file($this->tep)) return null;
        $noiDung = @file_get_contents($this->tep);
        if ($noiDung === false) return null;
        $ten = json_decode($noiDung, true);
        return is_string($ten) && preg_match('/^anh_[a-f0-9]{32}\.(jpg|png|webp)$/D', $ten) === 1
            ? $ten : null;
    }

    public function luu(string $ten): void
    {
        $thuMuc = dirname($this->tep);
        if (!is_dir($thuMuc) && !@mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu thông tin ảnh đại diện.');
        }
        if (@file_put_contents($this->tep, json_encode($ten, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
            throw new \RuntimeException('Không thể ghi nhớ ảnh đại diện. Vui lòng kiểm tra quyền ghi storage.');
        }
    }
}
