<?php
declare(strict_types=1);

/**
 * Luu gio dang soan va mon yeu thich theo ID tai khoan da xac thuc.
 * Gio dung transaction va INSERT nhieu dong, khong truy van trong vong lap.
 * Thu: dang nhap hai tai khoan, them mon, dang xuat va dang nhap lai.
 */
namespace App\Data;

use PDO;
use Throwable;

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

    public function luuGioHang(int $uid, array $gio): void
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
        $this->pdo->beginTransaction();
        try {
            $xoa = $this->pdo->prepare('DELETE FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ?');
            $xoa->execute([$uid]);
            if ($thamSo !== []) {
                $dauHoi = implode(',', array_fill(0, intdiv(count($thamSo), 3), '(?, ?, ?)'));
                $them = $this->pdo->prepare('INSERT INTO gio_hang_nguoi_dung (nguoi_dung_id, mon_hoc_id, so_luong) VALUES ' . $dauHoi);
                $them->execute($thamSo);
            }
            $this->pdo->commit();
        } catch (Throwable $loi) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $loi;
        }
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
