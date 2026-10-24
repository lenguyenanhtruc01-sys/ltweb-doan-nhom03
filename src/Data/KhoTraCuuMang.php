<?php
declare(strict_types=1);

/**
 * Lưu lịch sử tra cứu mạng của trang cá nhân vào MySQL thay cho JSONL.
 * Thử: tra cứu IP, xem lịch sử; F5 sau redirect không thêm dòng mới.
 * Chỉ lưu domain/IP, không lưu mật khẩu từ công cụ entropy.
 */
namespace App\Data;

use PDO;

final class KhoTraCuuMang
{
    public function __construct(private PDO $pdo)
    {
    }

    public function them(string $mssv, string $target, string $ip, string $type): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tv_tra_cuu_mang (mssv, muc_tieu, dia_chi_ip, loai_ip) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$mssv, $target, $ip, $type]);
    }

    public function ganDay(string $mssv): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT muc_tieu, dia_chi_ip, loai_ip, thoi_gian FROM tv_tra_cuu_mang
             WHERE mssv = ? ORDER BY id DESC LIMIT 10'
        );
        $stmt->execute([$mssv]);
        return $stmt->fetchAll();
    }
}
