<?php
declare(strict_types=1);

/**
 * Ghi lượt xem theo ngày bằng UPSERT và thống kê riêng từng MSSV.
 * Nhận PDO, thống kê 7 ngày bằng một GROUP BY rồi bổ sung ngày chưa có lượt.
 * Thử hai MSSV cùng ngày: trang Hải chỉ hiển thị số của Hải.
 */
namespace App\Data;

use DateTimeImmutable;
use PDO;

final class KhoLuotXem
{
    public function __construct(private PDO $pdo) {}

    public function ghiNhan(string $mssv, string $ngay): void
    {
        $st = $this->pdo->prepare('INSERT INTO tv_luot_xem (mssv,ngay,so_luot) VALUES (?,?,1)
            ON DUPLICATE KEY UPDATE so_luot=so_luot+1');
        $st->execute([$mssv, $ngay]);
    }

    public function bayNgay(string $mssv, DateTimeImmutable $homNay): array
    {
        $st = $this->pdo->prepare('SELECT ngay, SUM(so_luot) AS tong_luot FROM tv_luot_xem
            WHERE mssv=? AND ngay BETWEEN ? AND ? GROUP BY ngay ORDER BY ngay DESC');
        $st->execute([$mssv, $homNay->modify('-6 days')->format('Y-m-d'), $homNay->format('Y-m-d')]);
        $map = array_column($st->fetchAll(), 'tong_luot', 'ngay');
        $ds = [];
        for ($i=0; $i<7; $i++) {
            $ngay = $homNay->modify('-' . $i . ' days')->format('Y-m-d');
            $ds[] = ['ngay'=>$ngay, 'so_luot'=>(int) ($map[$ngay] ?? 0)];
        }
        return $ds;
    }
}
