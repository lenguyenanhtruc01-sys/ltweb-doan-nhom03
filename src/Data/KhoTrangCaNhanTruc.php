<?php

declare(strict_types=1);

/**
 * Đọc dự án và lưu kết quả GPA/quiz của Trúc trong MySQL bằng PDO.
 * Kết quả và reset giới hạn theo MSSV và phạm vi người xem do máy chủ xác định.
 * Thử tìm dự án, tính GPA, trả lời/reset quiz và hai phiên khách độc lập.
 */
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

    /** Kết quả thuộc người xem trang; mã phạm vi do máy chủ tạo, không nhận từ form. */
    public function luuGpa(string $mssv, string $phamVi, array $ketQua): void
    {
        $q = $this->pdo->prepare('INSERT INTO tv_truc_gpa (mssv, pham_vi, ket_qua) VALUES (?, ?, ?)');
        $q->execute([$mssv, $phamVi, json_encode($ketQua, JSON_THROW_ON_ERROR)]);
    }

    public function layGpa(string $mssv, string $phamVi): ?array
    {
        $q = $this->pdo->prepare('SELECT ket_qua FROM tv_truc_gpa WHERE mssv=? AND pham_vi=? ORDER BY id DESC LIMIT 1');
        $q->execute([$mssv, $phamVi]);
        $json = $q->fetchColumn();
        return $json === false ? null : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function luuQuiz(string $mssv, string $phamVi, string $cauHoi, string $daChon, string $dapAn): void
    {
        if (!in_array($daChon, ['a','b','c','d'], true) || !in_array($dapAn, ['a','b','c','d'], true)) {
            throw new \InvalidArgumentException('Đáp án không hợp lệ.');
        }
        $q = $this->pdo->prepare('INSERT INTO tv_truc_quiz (mssv, pham_vi, cau_hoi, da_chon, dap_an, chinh_xac) VALUES (?, ?, ?, ?, ?, ?)');
        $q->execute([$mssv, $phamVi, $cauHoi, $daChon, $dapAn, (int) ($daChon === $dapAn)]);
    }

    public function thongKeQuiz(string $mssv, string $phamVi): array
    {
        $q = $this->pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(chinh_xac),0) AS score FROM tv_truc_quiz WHERE mssv=? AND pham_vi=?');
        $q->execute([$mssv, $phamVi]);
        return array_map('intval', $q->fetch());
    }

    public function layQuizGanNhat(string $mssv, string $phamVi): ?array
    {
        $q = $this->pdo->prepare('SELECT chinh_xac AS correct, dap_an AS correct_answer FROM tv_truc_quiz WHERE mssv=? AND pham_vi=? ORDER BY id DESC LIMIT 1');
        $q->execute([$mssv, $phamVi]);
        $row = $q->fetch();
        return $row === false ? null : $row + $this->thongKeQuiz($mssv, $phamVi);
    }

    public function xoaQuiz(string $mssv, string $phamVi): void
    {
        $q = $this->pdo->prepare('DELETE FROM tv_truc_quiz WHERE mssv=? AND pham_vi=?');
        $q->execute([$mssv, $phamVi]);
    }
}
