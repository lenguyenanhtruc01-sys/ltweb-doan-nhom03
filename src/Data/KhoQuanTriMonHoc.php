<?php
declare(strict_types=1);

/**
 * Truy cập PDO cho danh sách, thêm, sửa, xóa môn học của quản trị.
 * Mọi giá trị từ form dùng tham số; JOIN danh mục, không truy vấn trong vòng lặp.
 * Thử qua quan-tri-mon-hoc.php: phân trang, mã trùng 1062 và xóa bị chặn 1451.
 */
namespace App\Data;

use PDO;

final class KhoQuanTriMonHoc
{
    public function __construct(private PDO $pdo) {}

    public function danhMuc(): array
    {
        return $this->pdo->query('SELECT id, ten FROM danh_muc ORDER BY ten')->fetchAll();
    }

    public function dem(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM mon_hoc')->fetchColumn();
    }

    public function danhSach(int $gioiHan, int $boQua): array
    {
        $st = $this->pdo->prepare('SELECT m.*, d.ten AS ten_danh_muc FROM mon_hoc m
            JOIN danh_muc d ON d.id=m.danh_muc_id ORDER BY m.id DESC LIMIT ? OFFSET ?');
        $st->execute([$gioiHan, $boQua]);
        return $st->fetchAll();
    }

    public function lay(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM mon_hoc WHERE id=?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function them(array $duLieu): int
    {
        $st = $this->pdo->prepare('INSERT INTO mon_hoc
            (danh_muc_id, ma_mon, ten_mon, so_tin_chi, so_cho, mo_ta, anh) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute($this->thamSo($duLieu));
        return (int) $this->pdo->lastInsertId();
    }

    public function sua(int $id, array $duLieu): bool
    {
        $st = $this->pdo->prepare('UPDATE mon_hoc SET danh_muc_id=?, ma_mon=?, ten_mon=?,
            so_tin_chi=?, so_cho=?, mo_ta=?, anh=? WHERE id=?');
        $st->execute([...$this->thamSo($duLieu), $id]);
        return $st->rowCount() > 0 || $this->lay($id) !== null;
    }

    public function xoa(int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM mon_hoc WHERE id=?');
        $st->execute([$id]);
        return $st->rowCount() === 1;
    }

    private function thamSo(array $d): array
    {
        return [$d['danh_muc_id'], $d['ma_mon'], $d['ten_mon'], $d['so_tin_chi'],
            $d['so_cho'], $d['mo_ta'], $d['anh']];
    }
}
