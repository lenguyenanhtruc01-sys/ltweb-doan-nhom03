<?php
declare(strict_types=1);

/**
 * Dữ liệu cá nhân Duy: món ăn, lịch sử chơi và check-in thời tiết theo MSSV.
 * Khách chọn món trong session; chỉ chủ trang được lưu thêm món dùng chung.
 * Thử danh sách sai, khách/chủ trang, thống kê và lọc riêng MSSV trên CSDL thử.
 */
namespace App\Data;

use PDO;

class KhoCaNhan
{
    public function __construct(private PDO $db) {}

    public function laChuTrang(string $mssv, int $uid): bool
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM thanh_vien WHERE mssv=? AND nguoi_dung_id=?');
        $st->execute([$mssv, $uid]);
        return (int) $st->fetchColumn() === 1;
    }

    // ============ CHỨC NĂNG 1: MÓN ĂN ============
    public static function kiemTraDanhSachMon(array $mangMon, string $bua): array
    {
        if (!in_array($bua, ['bat_ky', 'sang', 'trua', 'toi', 'dem'], true)) {
            throw new \DomainException('Vui lòng chọn bữa ăn hợp lệ.');
        }
        $ds = [];
        foreach ($mangMon as $mon) {
            if (!is_string($mon)) throw new \DomainException('Danh sách món không hợp lệ.');
            $mon = trim($mon);
            if ($mon === '') continue;
            if (mb_strlen($mon) > 100 || preg_match('/[\x00-\x1f\x7f]/', $mon)) {
                throw new \DomainException('Mỗi tên món tối đa 100 ký tự và không chứa ký tự điều khiển.');
            }
            if (!in_array($mon, $ds, true)) $ds[] = $mon;
        }
        if (count($ds) < 2 || count($ds) > 50) {
            throw new \DomainException('Vui lòng nhập từ 2 đến 50 món khác nhau.');
        }
        if (mb_strlen(implode("\n", $ds)) > 3000) {
            throw new \DomainException('Danh sách món tối đa 3000 ký tự.');
        }
        return $ds;
    }

    public function luuDanhSachMon(string $mssv, array $mangMon, string $bua): void
    {
        $mangMon = self::kiemTraDanhSachMon($mangMon, $bua);

        $st = $this->db->prepare('SELECT ten_mon FROM tv_mon_an WHERE mssv = ?');
        $st->execute([$mssv]);
        $dsHienCo = $st->fetchAll(PDO::FETCH_COLUMN);

        $monMoi = array_diff($mangMon, $dsHienCo);
        if (empty($monMoi)) return;

        $placeholders = implode(',', array_fill(0, count($monMoi), '(?, ?, ?)'));
        $sql = "INSERT INTO tv_mon_an (mssv, ten_mon, bua) VALUES $placeholders";
        
        $params = [];
        foreach ($monMoi as $m) {
            $params[] = $mssv;
            $params[] = $m;
            $params[] = $bua;
        }
        $this->db->prepare($sql)->execute($params);
    }

    public function layDanhSachMon(string $mssv): array
    {
        $st = $this->db->prepare('SELECT ten_mon FROM tv_mon_an WHERE mssv = ?');
        $st->execute([$mssv]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    public function chonMonNgauNhien(array $mangMon, string $bua): string
    {
        $ds = self::kiemTraDanhSachMon($mangMon, $bua);
        return $ds[random_int(0, count($ds) - 1)];
    }

    // ============ CHỨC NĂNG 2: OẲN TÙ TÌ (DB) ============
    public function ghiLichSuOtt(string $mssv, string $nguoi, string $may, string $ketQua): void
    {
        $st = $this->db->prepare('INSERT INTO tv_lich_su_ott (mssv, nguoi_chon, may_chon, ket_qua) VALUES (?, ?, ?, ?)');
        $st->execute([$mssv, $nguoi, $may, $ketQua]);
    }

    public function thongKeOtt(string $mssv): array
    {
        $st = $this->db->prepare('SELECT ket_qua, COUNT(*) AS so_van FROM tv_lich_su_ott WHERE mssv = ? GROUP BY ket_qua');
        $st->execute([$mssv]);
        $raw = $st->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'thang' => $raw['thang'] ?? 0,
            'thua'  => $raw['thua'] ?? 0,
            'hoa'   => $raw['hoa'] ?? 0,
        ];
    }

    public function layLichSuOtt(string $mssv): array
    {
        $st = $this->db->prepare('SELECT * FROM tv_lich_su_ott WHERE mssv = ? ORDER BY id DESC LIMIT 10');
        $st->execute([$mssv]);
        return $st->fetchAll();
    }

    // ============ CHỨC NĂNG 3: CHECK-IN THỜI TIẾT ============
    public function themCheckIn(string $mssv, string $trangThai): void
    {
        if (!in_array($trangThai, ['nang','mua','may','giong_bao'], true)) {
            throw new \DomainException('Trạng thái thời tiết không hợp lệ.');
        }
        $st = $this->db->prepare('INSERT INTO tv_thoi_tiet_duy (mssv,trang_thai) VALUES (?,?)');
        $st->execute([$mssv, $trangThai]);
    }

    public function thongKeThoiTiet(string $mssv): array
    {
        $st = $this->db->prepare('SELECT trang_thai, COUNT(*) AS so_luot FROM tv_thoi_tiet_duy
            WHERE mssv=? GROUP BY trang_thai ORDER BY so_luot DESC, trang_thai');
        $st->execute([$mssv]);
        return $st->fetchAll();
    }
}
