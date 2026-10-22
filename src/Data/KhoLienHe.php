<?php
declare(strict_types=1);

/**
 * Đọc và ghi liên hệ JSONL, trả bản mới nhất trước.
 * Kiểm tra lỗi đọc/ghi thay vì truyền false vào xử lý mảng.
 */
namespace App\Data;

class KhoLienHe
{
    public function __construct(private string $tep) {}

    public function them(array $lh): void
    {
        $thuMuc = dirname($this->tep);

        if (!is_dir($thuMuc) && !mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu liên hệ.');
        }

        $dong = json_encode($lh,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $soByteDaGhi = file_put_contents(
            $this->tep,
            $dong . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($soByteDaGhi === false) {
            throw new \RuntimeException('Không thể lưu liên hệ.');
        }
    }

    public function tatCa(): array                  // mới nhất đứng đầu
    {
        if (!is_file($this->tep)) return [];
        $dong = @file($this->tep,
                     FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($dong === false) {
            throw new \RuntimeException('Không thể đọc danh sách liên hệ.');
        }
        $ds = array_map(fn($d) => json_decode($d, true), $dong);
        return array_reverse(array_filter($ds, 'is_array'));
    }
}
