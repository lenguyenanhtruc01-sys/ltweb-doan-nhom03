<?php

declare(strict_types=1);

namespace App\Data;

use PDO;

final class KhoTrangCaNhanTruc
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Lấy danh sách dự án của Trúc.
     */
    public function layDuAn(
        string $mssv,
        string $tuKhoa = '',
        string $sapXep = 'moi'
    ): array {
        $sql = '
            SELECT
                id,
                ten_du_an,
                mo_ta,
                cong_nghe,
                nam_thuc_hien
            FROM tv_du_an
            WHERE mssv = ?
        ';

        $thamSo = [$mssv];

        if ($tuKhoa !== '') {
            $tuKhoaAnToan = '%' . addcslashes($tuKhoa, '%_\\') . '%';

            $sql .= '
                AND (
                    ten_du_an LIKE ?
                    OR cong_nghe LIKE ?
                )
            ';

            $thamSo[] = $tuKhoaAnToan;
            $thamSo[] = $tuKhoaAnToan;
        }

        $orderBy = match ($sapXep) {
            'cu' => 'nam_thuc_hien ASC, id ASC',
            'ten' => 'ten_du_an ASC, id DESC',
            default => 'nam_thuc_hien DESC, id DESC',
        };

        $sql .= ' ORDER BY ' . $orderBy;

        $truyVan = $this->pdo->prepare($sql);
        $truyVan->execute($thamSo);

        return $truyVan->fetchAll();
    }
}
