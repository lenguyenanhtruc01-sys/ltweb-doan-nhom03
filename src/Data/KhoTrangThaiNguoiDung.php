<?php
declare(strict_types=1);

/**
 * Luu gio dang soan va mon yeu thich theo ID tai khoan da xac thuc.
 * Moi thao tac chi sua mon dich; gop gio khach bang mot INSERT nhieu dong.
 * Thu: cung tai khoan tren hai trinh duyet, them/xoa mon roi dang xuat.
 */
namespace App\Data;

use PDO;

final class KhoTrangThaiNguoiDung
{
    public function __construct(private PDO $pdo) {}

    public function gioHang(int $uid): array
    {
        $st = $this->pdo->prepare('SELECT mon_hoc_id, so_luong FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ?');
        $st->execute([$uid]);
        $gio = [];
        foreach ($st->fetchAll() as $dong) {
            $gio[(int) $dong['mon_hoc_id']] = (int) $dong['so_luong'];
        }
        return $gio;
    }

    public function gopGioKhach(int $uid, array $gio): void
    {
        $hopLe = [];
        foreach ($gio as $id => $sl) {
            if (ctype_digit((string) $id) && (int) $id > 0 && is_int($sl) && $sl >= 1 && $sl <= 5) {
                $hopLe[(int) $id] = $sl;
            }
        }
        $monConTonTai = (new KhoMonHoc($this->pdo))->theoNhieuId(array_keys($hopLe));
        $thamSo = [];
        foreach (array_keys($monConTonTai) as $id) {
            array_push($thamSo, $uid, $id, $hopLe[$id]);
        }
        if ($thamSo !== []) {
            $dauHoi = implode(',', array_fill(0, intdiv(count($thamSo), 3), '(?, ?, ?)'));
            $them = $this->pdo->prepare(
                'INSERT INTO gio_hang_nguoi_dung (nguoi_dung_id, mon_hoc_id, so_luong) VALUES ' . $dauHoi
                . ' ON DUPLICATE KEY UPDATE so_luong = gio_hang_nguoi_dung.so_luong'
            );
            $them->execute($thamSo);
        }
    }

    public function themMon(int $uid, int $id, int $soLuong): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO gio_hang_nguoi_dung (nguoi_dung_id, mon_hoc_id, so_luong)
             SELECT ?, id, ? FROM mon_hoc WHERE id = ?
             ON DUPLICATE KEY UPDATE so_luong = gio_hang_nguoi_dung.so_luong'
        );
        $st->execute([$uid, $soLuong, $id]);
    }

    public function capNhatMon(int $uid, int $id, int $soLuong): void
    {
        $st = $this->pdo->prepare('UPDATE gio_hang_nguoi_dung SET so_luong = ? WHERE nguoi_dung_id = ? AND mon_hoc_id = ?');
        $st->execute([$soLuong, $uid, $id]);
    }

    public function xoaMon(int $uid, int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ? AND mon_hoc_id = ?');
        $st->execute([$uid, $id]);
        return $st->rowCount() === 1;
    }

    public function xoaGioHang(int $uid): void
    {
        $st = $this->pdo->prepare('DELETE FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ?');
        $st->execute([$uid]);
    }

    public function yeuThich(int $uid): array
    {
        $st = $this->pdo->prepare('SELECT mon_hoc_id FROM mon_yeu_thich WHERE nguoi_dung_id = ? ORDER BY mon_hoc_id');
        $st->execute([$uid]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    public function datYeuThich(int $uid, int $id, bool $chon): void
    {
        $sql = $chon
            ? 'INSERT INTO mon_yeu_thich (nguoi_dung_id, mon_hoc_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE mon_hoc_id = VALUES(mon_hoc_id)'
            : 'DELETE FROM mon_yeu_thich WHERE nguoi_dung_id = ? AND mon_hoc_id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute([$uid, $id]);
    }
}
