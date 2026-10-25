<?php
declare(strict_types=1);

namespace App\Data;

use PDO;

class KhoCaNhan
{
    public function __construct(private PDO $db) {}

    // ============ CHỨC NĂNG 1: MÓN ĂN ============
    public function luuDanhSachMon(string $mssv, array $mangMon, string $bua): void
    {
        if (empty($mangMon)) return;

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

    // HÀM ĐANG BỊ THIẾU GÂY RA LỖI
    public function layDanhSachMon(string $mssv): array
    {
        $st = $this->db->prepare('SELECT ten_mon FROM tv_mon_an WHERE mssv = ?');
        $st->execute([$mssv]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    public function chonMonNgauNhien(string $mssv): string|false
    {
        $st = $this->db->prepare('SELECT ten_mon FROM tv_mon_an WHERE mssv = ? ORDER BY RAND() LIMIT 1');
        $st->execute([$mssv]);
        return $st->fetchColumn();
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
    public function themCheckIn(string $trangThai): void
    {
        $st = $this->db->prepare('INSERT INTO tv_thoi_tiet_duy (trang_thai) VALUES (?)');
        $st->execute([$trangThai]);
    }

    public function thongKeThoiTiet(): array
    {
        $st = $this->db->query('SELECT trang_thai, COUNT(*) AS so_luot FROM tv_thoi_tiet_duy GROUP BY trang_thai ORDER BY so_luot DESC');
        return $st->fetchAll();
    }
}