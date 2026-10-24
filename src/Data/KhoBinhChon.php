<?php
declare(strict_types=1);

/**
 * Lưu bình chọn một lần theo người dùng và thống kê bằng PDO.
 * Thử: gửi hai phiếu cho cùng MSSV/tài khoản để kiểm tra lỗi UNIQUE 1062.
 * Thống kê GROUP BY được dùng để tính số phiếu và tỉ lệ trên trang Hợp.
 */
namespace App\Data;

use PDO;
use PDOException;
use DomainException;

final class KhoBinhChon
{
    public const TUY_CHON = ['huu_ich' => 'Hữu ích', 'sang_tao' => 'Sáng tạo', 'de_hieu' => 'Dễ hiểu'];

    public function __construct(private PDO $pdo)
    {
    }

    public function boPhieu(string $mssv, int $userId, string $option): void
    {
        if (!isset(self::TUY_CHON[$option])) {
            throw new DomainException('Lựa chọn bình chọn không hợp lệ.');
        }
        $user = $this->pdo->prepare('SELECT id FROM nguoi_dung WHERE id = ?');
        $user->execute([$userId]);
        if ($user->fetchColumn() === false) {
            throw new DomainException('Vui lòng đăng nhập lại bằng tài khoản MySQL.');
        }
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO tv_binh_chon (mssv, nguoi_dung_id, tuy_chon) VALUES (?, ?, ?)'
            );
            $stmt->execute([$mssv, $userId, $option]);
        } catch (PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('Bạn đã bình chọn rồi', 0, $error);
            }
            throw $error;
        }
    }

    public function thongKe(string $mssv): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tuy_chon, COUNT(*) AS so_phieu FROM tv_binh_chon
             WHERE mssv = ? GROUP BY tuy_chon ORDER BY tuy_chon'
        );
        $stmt->execute([$mssv]);
        return $stmt->fetchAll();
    }
}
