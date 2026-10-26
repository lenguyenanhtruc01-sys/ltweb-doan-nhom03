<?php
declare(strict_types=1);

/**
 * Tệp: src/Data/KhoMonHoc.php
 * Chức năng: Lớp truy cập dữ liệu cho môn học (đã chuyển sang MySQL).
 * Thử qua danh sách/chi tiết: tìm, lọc, phân trang, ID đúng và ID không tồn tại.
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
        $st = $this->db->prepare('SELECT m.*, d.ten AS ten_danh_muc FROM mon_hoc m
            JOIN danh_muc d ON d.id=m.danh_muc_id WHERE m.id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? new MonHoc($row) : null;
    }

    /** Bốn môn mới nhất; ID quyết định thứ tự khi ngày tạo bằng nhau. */
    public function moiNhat(): array
    {
        $st = $this->db->query('SELECT * FROM mon_hoc ORDER BY ngay_tao DESC, id DESC LIMIT 4');
        return array_map(static fn (array $row): MonHoc => new MonHoc($row), $st->fetchAll());
    }

    /** Lấy tối đa bốn môn khác cùng danh mục bằng một truy vấn. */
    public function cungDanhMuc(int $danhMucId, int $loaiTruId): array
    {
        $st = $this->db->prepare('SELECT * FROM mon_hoc WHERE danh_muc_id=? AND id<>?
            ORDER BY ngay_tao DESC, id DESC LIMIT 4');
        $st->execute([$danhMucId, $loaiTruId]);
        return array_map(static fn (array $row): MonHoc => new MonHoc($row), $st->fetchAll());
    }

    /** Lấy cả giỏ bằng một truy vấn; vòng lặp chỉ chuyển đổi model. */
    public function theoNhieuId(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare('SELECT * FROM mon_hoc WHERE id IN (' . $marks . ')');
        $stmt->execute(array_values($ids));
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['id']] = new MonHoc($row);
        }
        return $result;
    }

    /** Đọc tối đa 20 mục cookie, lấy một lần bằng IN và giữ thứ tự xem. */
    public function daXemGanDay(mixed $cookie, int $gioiHan = 4, ?int $loaiTruId = null): array
    {
        if (!is_string($cookie) || $cookie === '' || $gioiHan <= 0) return [];
        $ids = [];
        foreach (array_slice(explode(',', $cookie), 0, 20) as $raw) {
            $id = filter_var(trim($raw), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false && $id !== $loaiTruId) $ids[$id] = $id;
        }
        $cacMon = $this->theoNhieuId(array_values($ids));
        $ketQua = [];
        foreach ($ids as $id) {
            if (!isset($cacMon[$id])) continue;
            $ketQua[] = $cacMon[$id];
            if (count($ketQua) >= $gioiHan) break;
        }
        return $ketQua;
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
        // Hàm lấy danh sách danh mục cho ô lọc
    public function layDanhMuc(): array
    {
        return $this->db->query('SELECT id, ten FROM danh_muc ORDER BY ten')->fetchAll();
    }
}
