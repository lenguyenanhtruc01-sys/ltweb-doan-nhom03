<?php
/**
 * Tệp: src/Data/KhoKeHoach.php
 * DAO cho kế hoạch học tập — nhận PDO qua constructor.
 * Tạo kế hoạch + chi tiết trong MỘT transaction, snapshot thuộc tính môn.
 */
declare(strict_types=1);

namespace App\Data;

use PDO;
use RuntimeException;
use Throwable;

final class MonKhongTonTaiException extends RuntimeException {}

final class KhoKeHoach
{
    public const TRANG_THAI_HOP_LE = ['cho_duyet', 'da_duyet', 'tu_choi'];

    public function __construct(private PDO $db) {}

    public static function nhanTrangThai(string $tt): string
    {
        return match ($tt) {
            'cho_duyet' => 'Chờ duyệt',
            'da_duyet'  => 'Đã duyệt',
            'tu_choi'   => 'Từ chối',
            default     => $tt,
        };
    }

    public function taoKeHoach(int $nguoiDungId, array $gioSession, string $ghiChu = ''): int
    {
        if ($nguoiDungId <= 0) {
            throw new RuntimeException('Chưa đăng nhập.');
        }
        if ($gioSession === []) {
            throw new RuntimeException('Kế hoạch trống.');
        }

        $ids        = [];
        $soLuongMap = [];
        foreach ($gioSession as $id => $sl) {
            $id = (int) $id;
            $sl = (int) $sl;
            if ($id > 0 && $sl >= 1 && $sl <= 5) {
                $ids[$id]        = $id;
                $soLuongMap[$id] = $sl;
            }
        }
        if ($ids === []) {
            throw new RuntimeException('Không có môn hợp lệ trong kế hoạch.');
        }

        $this->db->beginTransaction();
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = $this->db->prepare(
                "SELECT id, ma_mon, ten_mon, so_tin_chi
                 FROM mon_hoc WHERE id IN ($ph)"
            );
            $st->execute(array_values($ids));
            $dsMon = $st->fetchAll();

            if (count($dsMon) !== count($ids)) {
                throw new MonKhongTonTaiException('Có môn không tồn tại.');
            }

            $st = $this->db->prepare(
                "INSERT INTO ke_hoach_hoc_tap (nguoi_dung_id, trang_thai, ghi_chu)
                 VALUES (?, 'cho_duyet', ?)"
            );
            $st->execute([$nguoiDungId, $ghiChu]);
            $keHoachId = (int) $this->db->lastInsertId();

            $st = $this->db->prepare(
                "INSERT INTO chi_tiet_ke_hoach
                 (ke_hoach_id, mon_hoc_id, ma_mon, ten_mon, so_tin_chi, so_luong)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($dsMon as $m) {
                $st->execute([
                    $keHoachId,
                    (int) $m['id'],
                    (string) $m['ma_mon'],
                    (string) $m['ten_mon'],
                    (int) $m['so_tin_chi'],
                    $soLuongMap[(int) $m['id']],
                ]);
            }

            // Giỏ tài khoản được xóa cùng giao dịch gửi kế hoạch thành công.
            $xoaGio = $this->db->prepare('DELETE FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ?');
            $xoaGio->execute([$nguoiDungId]);
            $this->db->commit();
            return $keHoachId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function dsCuaToi(int $nguoiDungId): array
    {
        $st = $this->db->prepare(
            'SELECT k.id, k.trang_thai, k.ngay_tao,
                    COUNT(ct.mon_hoc_id) AS so_mon,
                    COALESCE(SUM(ct.so_tin_chi * ct.so_luong), 0) AS tong_tin_chi
             FROM ke_hoach_hoc_tap k
             LEFT JOIN chi_tiet_ke_hoach ct ON ct.ke_hoach_id = k.id
             WHERE k.nguoi_dung_id = ?
             GROUP BY k.id, k.trang_thai, k.ngay_tao
             ORDER BY k.ngay_tao DESC, k.id DESC'
        );
        $st->execute([$nguoiDungId]);
        return $st->fetchAll();
    }

    public function dsTatCa(): array
    {
        $st = $this->db->query(
            'SELECT k.id, k.trang_thai, k.ngay_tao,
                    nd.ho_ten, nd.email,
                    COUNT(ct.mon_hoc_id) AS so_mon,
                    COALESCE(SUM(ct.so_tin_chi * ct.so_luong), 0) AS tong_tin_chi
             FROM ke_hoach_hoc_tap k
             JOIN nguoi_dung nd ON nd.id = k.nguoi_dung_id
             LEFT JOIN chi_tiet_ke_hoach ct ON ct.ke_hoach_id = k.id
             GROUP BY k.id, k.trang_thai, k.ngay_tao, nd.ho_ten, nd.email
             ORDER BY k.ngay_tao DESC, k.id DESC'
        );
        return $st->fetchAll();
    }

    public function chiTiet(int $keHoachId, int $nguoiDungId, bool $laQuanTri): ?array
    {
        $sql = 'SELECT id, nguoi_dung_id, trang_thai, ghi_chu, ngay_tao
                FROM ke_hoach_hoc_tap WHERE id = ?';
        $ts = [$keHoachId];
        if (!$laQuanTri) {
            $sql .= ' AND nguoi_dung_id = ?';
            $ts[] = $nguoiDungId;
        }
        $st = $this->db->prepare($sql);
        $st->execute($ts);
        $keHoach = $st->fetch();
        if ($keHoach === false) {
            return null;
        }

        $st = $this->db->prepare(
            'SELECT mon_hoc_id, ma_mon, ten_mon, so_tin_chi, so_luong
             FROM chi_tiet_ke_hoach WHERE ke_hoach_id = ? ORDER BY ma_mon'
        );
        $st->execute([$keHoachId]);
        $keHoach['chi_tiet'] = $st->fetchAll();
        return $keHoach;
    }

    public function doiTrangThai(int $keHoachId, string $trangThai): bool
    {
        if (!in_array($trangThai, self::TRANG_THAI_HOP_LE, true)) {
            throw new RuntimeException('Trạng thái không hợp lệ.');
        }
        $st = $this->db->prepare(
            'UPDATE ke_hoach_hoc_tap SET trang_thai = ? WHERE id = ?'
        );
        $st->execute([$trangThai, $keHoachId]);
        return $st->rowCount() === 1;
    }
}
