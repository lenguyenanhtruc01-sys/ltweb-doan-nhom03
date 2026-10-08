<?php
namespace App\Services;

use App\Data\KhoMonHoc;

class KeHoachHocTap {
    private const SESSION_KEY = 'ke_hoach_dang_ky';
    private $khoMonHoc;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $this->khoMonHoc = new KhoMonHoc();
    }

    // Thêm môn học với số lượng/nhóm đăng ký (có validate min_range, max_range, tồn tại ID)
    public function them($id, $soLuong = 1) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) return false;

        // Kiểm tra ID phải có thực trong dữ liệu nguồn
        $monHoc = $this->khoMonHoc->timTheoId($id);
        if (!$monHoc) return false;

        // Validate min_range và max_range cho số lượng (ví dụ: từ 1 đến 5 nhóm/lớp)
        $soLuong = filter_var($soLuong, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1, "max_range" => 5]
        ]);
        if ($soLuong === false) return false;

        // Nếu đã có thì cộng dồn hoặc cập nhật
        if (isset($_SESSION[self::SESSION_KEY][$id])) {
            $_SESSION[self::SESSION_KEY][$id] += $soLuong;
        } else {
            $_SESSION[self::SESSION_KEY][$id] = $soLuong;
        }
        return true;
    }

    // Cập nhật đổi số lượng trực tiếp
    public function capNhat($id, $soLuong) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $soLuong = filter_var($soLuong, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 0, "max_range" => 5]
        ]);

        if ($id === false || $soLuong === false) return false;

        if ($soLuong === 0) {
            $this->xoa($id);
        } else {
            // Kiểm tra ID có tồn tại không trước khi cập nhật
            if ($this->khoMonHoc->timTheoId($id)) {
                $_SESSION[self::SESSION_KEY][$id] = $soLuong;
            }
        }
        return true;
    }

    // Xóa một mục
    public function xoa($id) {
        if (isset($_SESSION[self::SESSION_KEY][$id])) {
            unset($_SESSION[self::SESSION_KEY][$id]);
        }
    }

    // Xóa hết giỏ hàng / kế hoạch
    public function xoaTatCa() {
        $_SESSION[self::SESSION_KEY] = [];
    }

    // Lấy chi tiết các mục kèm thông tin chuẩn từ dữ liệu gốc + tính tổng
    public function layDanhSachChiTiet() {
        $chiTiet = [];
        $tongSoTinChi = 0;
        $tongSoMon = 0;

        foreach ($_SESSION[self::SESSION_KEY] as $id => $soLuong) {
            $mon = $this->khoMonHoc->timTheoId($id);
            if ($mon) {
                // Lấy thông tin số tín chỉ gốc từ dữ liệu (KHÔNG lấy từ form)
                $tinChiMon = $mon->soTinChi ?? 0;
                $tongTinChiMon = $tinChiMon * $soLuong;

                $chiTiet[] = [
                    'id' => $mon->id,
                    'maMon' => $mon->maMon,
                    'tenMon' => $mon->tenMon,
                    'soTinChi' => $tinChiMon,
                    'soLuong' => $soLuong,
                    'tongTinChi' => $tongTinChiMon
                ];

                $tongSoTinChi += $tongTinChiMon;
                $tongSoMon += $soLuong;
            } else {
                // Nếu ID trong session không còn tồn tại trong file JSON thì xóa bỏ
                unset($_SESSION[self::SESSION_KEY][$id]);
            }
        }

        return [
            'danh_sach' => $chiTiet,
            'tong_so_mon' => $tongSoMon,
            'tong_so_tin_chi' => $tongSoTinChi
        ];
    }
}