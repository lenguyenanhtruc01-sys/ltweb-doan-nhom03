<?php
declare(strict_types=1);

/**
 * Tạo kế hoạch, lưu snapshot và trừ số chỗ bằng UPDATE có điều kiện.
 * Thiếu chỗ ở bất kỳ môn nào sẽ rollback toàn bộ, giữ giỏ để thử lại.
 * Thử: hai môn còn chỗ; môn thứ hai hết chỗ; hai tài khoản tranh chỗ cuối.
 */

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
        if ($nguoiDungId <= 0 || $gioSession === []) {
            throw new RuntimeException('Dữ liệu không hợp lệ.');
        }

        $ids = []; $soLuongMap = [];
        foreach ($gioSession as $id => $sl) {
            if ((is_int($id) || is_string($id)) && ctype_digit((string) $id)
                && is_int($sl) && (int)$id > 0 && $sl >= 1 && $sl <= 5) {
                $ids[(int)$id] = (int)$id;
                $soLuongMap[(int)$id] = (int)$sl;
            } else {
                throw new RuntimeException('Dữ liệu môn hoặc số lượng không hợp lệ.');
            }
        }
        if ($ids === []) throw new RuntimeException('Không có môn hợp lệ.');

        $this->db->beginTransaction();
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = $this->db->prepare("SELECT id, ma_mon, ten_mon, so_tin_chi FROM mon_hoc WHERE id IN ($ph) ORDER BY id");
            $st->execute(array_values($ids));
            $dsMon = $st->fetchAll();

            if (count($dsMon) !== count($ids)) {
                throw new MonKhongTonTaiException('Có môn không tồn tại.');
            }

            // 1. Insert bản ghi cha (Kế hoạch)
            $st = $this->db->prepare("INSERT INTO ke_hoach_hoc_tap (nguoi_dung_id, trang_thai, ghi_chu) VALUES (?, 'cho_duyet', ?)");
            $st->execute([$nguoiDungId, $ghiChu]);
            $keHoachId = (int) $this->db->lastInsertId();

            $stInsert = $this->db->prepare("INSERT INTO chi_tiet_ke_hoach (ke_hoach_id, mon_hoc_id, ma_mon, ten_mon, so_tin_chi, so_luong) VALUES (?, ?, ?, ?, ?, ?)");
            
            // 2. Chuẩn bị sẵn câu lệnh UPDATE có điều kiện để trừ số chỗ
            $stUpdate = $this->db->prepare("UPDATE mon_hoc SET so_cho = so_cho - ? WHERE id = ? AND so_cho >= ?");

            foreach ($dsMon as $m) {
                $monId = (int)$m['id'];
                $sl = $soLuongMap[$monId];

                // 3. Thực hiện trừ số chỗ
                $stUpdate->execute([$sl, $monId, $sl]);
                
                // 4. Kiểm tra rowCount() - Nếu không trừ được (do hết chỗ) thì rollback
                if ($stUpdate->rowCount() !== 1) {
                    throw new MonKhongDuChoException("Môn {$m['ma_mon']} không còn đủ chỗ đăng ký.");
                }

                // 5. Lưu chi tiết (snapshot)
                $stInsert->execute([
                    $keHoachId, $monId,
                    (string)$m['ma_mon'], (string)$m['ten_mon'],
                    (int)$m['so_tin_chi'], $sl
                ]);
            }

            // Chỉ xóa các môn/số lượng đã gửi; giữ môn phiên khác vừa thêm/sửa.
            $dieuKienXoa = [];
            $thamSoXoa = [$nguoiDungId];
            foreach ($dsMon as $m) {
                $dieuKienXoa[] = '(mon_hoc_id = ? AND so_luong = ?)';
                array_push($thamSoXoa, (int) $m['id'], $soLuongMap[(int) $m['id']]);
            }
            $xoaGio = $this->db->prepare(
                'DELETE FROM gio_hang_nguoi_dung WHERE nguoi_dung_id = ? AND ('
                . implode(' OR ', $dieuKienXoa) . ')'
            );
            $xoaGio->execute($thamSoXoa);
            $this->db->commit();
            return $keHoachId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function dsCuaToi(int $nguoiDungId): array {
        $st = $this->db->prepare('SELECT k.id, k.trang_thai, k.ngay_tao, COUNT(ct.mon_hoc_id) AS so_mon, COALESCE(SUM(ct.so_tin_chi * ct.so_luong), 0) AS tong_tin_chi FROM ke_hoach_hoc_tap k LEFT JOIN chi_tiet_ke_hoach ct ON ct.ke_hoach_id = k.id WHERE k.nguoi_dung_id = ? GROUP BY k.id, k.trang_thai, k.ngay_tao ORDER BY k.ngay_tao DESC, k.id DESC');
        $st->execute([$nguoiDungId]);
        return $st->fetchAll();
    }

    public function dsTatCa(): array {
        $st = $this->db->query('SELECT k.id, k.trang_thai, k.ngay_tao, nd.ho_ten, nd.email, COUNT(ct.mon_hoc_id) AS so_mon, COALESCE(SUM(ct.so_tin_chi * ct.so_luong), 0) AS tong_tin_chi FROM ke_hoach_hoc_tap k JOIN nguoi_dung nd ON nd.id = k.nguoi_dung_id LEFT JOIN chi_tiet_ke_hoach ct ON ct.ke_hoach_id = k.id GROUP BY k.id, k.trang_thai, k.ngay_tao, nd.ho_ten, nd.email ORDER BY k.ngay_tao DESC, k.id DESC');
        return $st->fetchAll();
    }

    public function chiTiet(int $keHoachId, int $nguoiDungId, bool $laQuanTri): ?array {
        $sql = 'SELECT id, nguoi_dung_id, trang_thai, ghi_chu, ngay_tao FROM ke_hoach_hoc_tap WHERE id = ?';
        $ts = [$keHoachId];
        if (!$laQuanTri) { $sql .= ' AND nguoi_dung_id = ?'; $ts[] = $nguoiDungId; }
        $st = $this->db->prepare($sql); $st->execute($ts);
        $keHoach = $st->fetch();
        if ($keHoach === false) return null;

        $st = $this->db->prepare('SELECT mon_hoc_id, ma_mon, ten_mon, so_tin_chi, so_luong FROM chi_tiet_ke_hoach WHERE ke_hoach_id = ? ORDER BY ma_mon');
        $st->execute([$keHoachId]);
        $keHoach['chi_tiet'] = $st->fetchAll();
        return $keHoach;
    }

    public function doiTrangThai(int $keHoachId, string $trangThai): bool {
        if (!in_array($trangThai, self::TRANG_THAI_HOP_LE, true)) {
            throw new RuntimeException('Trạng thái không hợp lệ.');
        }
        $st = $this->db->prepare('UPDATE ke_hoach_hoc_tap SET trang_thai = ? WHERE id = ?');
        $st->execute([$trangThai, $keHoachId]);
        return $st->rowCount() === 1;
    }
}
