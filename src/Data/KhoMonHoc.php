<?php
declare(strict_types=1);

/**
 * Tệp: src/Data/KhoMonHoc.php
 * Chức năng: Lớp truy cập dữ liệu cho môn học (đã chuyển sang MySQL).
 */

namespace App\Data;

use App\Models\MonHoc;
use PDO;

class KhoMonHoc
{
    private PDO $db;

    // Nhận kết nối PDO qua constructor (Slide 23)
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** @return list<MonHoc> */
    public function tatCa(): array
    {
        $st = $this->db->query("SELECT * FROM mon_hoc ORDER BY id DESC");
        $danhSach = [];
        foreach ($st->fetchAll() as $row) {
            $danhSach[] = new MonHoc($row);
        }
        return $danhSach;
    }

    public function timTheoId(int $id): ?MonHoc
    {
        $st = $this->db->prepare("SELECT * FROM mon_hoc WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? new MonHoc($row) : null;
    }

    // ==========================================
    // CÁC HÀM DÙNG CHO CHỨC NĂNG 2 (TÌM KIẾM, LỌC, PHÂN TRANG)
    // ==========================================

    // Hàm dùng chung xây dựng điều kiện WHERE (Gợi ý 3)
    private function dieuKien(array $loc): array
    {
        $dk = []; // các đoạn điều kiện
        $ts = []; // tham số theo thứ tự dấu ?

        // 1. Tìm theo từ khóa (tìm trong tên hoặc mã môn)
        if ($loc['q'] !== '') {
            $dk[] = '(ten_mon LIKE ? OR ma_mon LIKE ?)';
            $ts[] = '%' . addcslashes($loc['q'], '%_\\') . '%';
            $ts[] = '%' . addcslashes($loc['q'], '%_\\') . '%';
        }

        // 2. Lọc theo danh mục
        if ($loc['dm']) {
            $dk[] = 'danh_muc_id = ?';
            $ts[] = $loc['dm'];
        }
        if ($loc['tcToiDa'] !== null) {
            $dk[] = 'so_tin_chi <= ?';
            $ts[] = $loc['tcToiDa'];
        }

        $where = $dk ? 'WHERE ' . implode(' AND ', $dk) : '';
        return [$where, $ts];
    }

    // Đếm tổng số dòng khớp bộ lọc (để tính số trang)
    public function dem(array $loc): int
    {
        [$where, $ts] = $this->dieuKien($loc);
        $st = $this->db->prepare("SELECT COUNT(*) FROM mon_hoc $where");
        $st->execute($ts);
        return (int) $st->fetchColumn();
    }

    // Lấy dữ liệu của một trang
    public function trang(array $loc, string $sx, int $soDong, int $boQua): array
    {
        [$where, $ts] = $this->dieuKien($loc);

        // Sắp xếp an toàn bằng match (Slide 30)
        $orderBy = match ($sx) {
            'ten' => 'ten_mon ASC, id',
            'tin-chi-tang' => 'so_tin_chi ASC, id',
            'tin-chi-giam' => 'so_tin_chi DESC, id',
            default => 'id DESC', // 'moi' hoặc giá trị lạ
        };

        $st = $this->db->prepare("SELECT * FROM mon_hoc $where ORDER BY $orderBy LIMIT ? OFFSET ?");
        
        // Trải mảng tham số lọc, sau đó thêm số dòng và vị trí bắt đầu
        $st->execute([...$ts, $soDong, $boQua]);

        $danhSach = [];
        foreach ($st->fetchAll() as $row) {
            $danhSach[] = new MonHoc($row);
        }
        return $danhSach;
    }
}