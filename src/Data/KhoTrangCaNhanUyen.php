<?php
declare(strict_types=1);

/**
 * Đọc kỹ năng và tìm môn trong thời khóa biểu của Uyên bằng PDO.
 * Mọi kết quả giới hạn theo MSSV; tìm kiếm escape ký tự LIKE.
 * Thử lọc nhóm, tìm tên/mã/phòng và từ khóa có dấu nháy trên trang Uyên.
 */
namespace App\Data;

use PDO;

final class KhoTrangCaNhanUyen
{
    public function __construct(private PDO $pdo)
    {
    }

    public function layKyNang(string $mssv, string $nhom): array
    {
        $sql = 'SELECT ten FROM tv_uyen_ky_nang WHERE mssv = ?';
        $thamSo = [$mssv];
        if ($nhom !== 'tat-ca') {
            $sql .= ' AND nhom = ?';
            $thamSo[] = $nhom;
        }
        $sql .= ' ORDER BY id';

        $truyVan = $this->pdo->prepare($sql);
        $truyVan->execute($thamSo);
        return $truyVan->fetchAll();
    }

    public function timThoiKhoaBieu(string $mssv, string $tuKhoa): array
    {
        $sql = 'SELECT ma_mon, ten_mon, ma_lop, thu, tiet_bat_dau, tiet_ket_thuc, phong
                FROM tv_uyen_thoi_khoa_bieu
                WHERE mssv = ?';
        $thamSo = [$mssv];
        if ($tuKhoa !== '') {
            $tuKhoaAnToan = '%' . addcslashes($tuKhoa, '%_\\') . '%';
            $sql .= ' AND (ma_mon LIKE ? OR ten_mon LIKE ? OR ma_lop LIKE ? OR phong LIKE ?)';
            array_push($thamSo, $tuKhoaAnToan, $tuKhoaAnToan, $tuKhoaAnToan, $tuKhoaAnToan);
        }
        $sql .= ' ORDER BY thu, tiet_bat_dau, ten_mon';

        $truyVan = $this->pdo->prepare($sql);
        $truyVan->execute($thamSo);
        return $truyVan->fetchAll();
    }
}
