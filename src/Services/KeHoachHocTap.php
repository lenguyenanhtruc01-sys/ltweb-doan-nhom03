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

    // Thêm môn học với số lượng/nhóm đăng ký
    public function them($id, $soLuong = 1) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) return false;

        $monHoc = $this->khoMonHoc->timTheoId($id);
        if (!$monHoc) return false;

        $soLuong = filter_var($soLuong, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1, "max_range" => 5]
        ]);
        if ($soLuong === false) return false;

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

    // Xóa hết kế hoạch
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
                // Ép đối tượng thành mảng để truy xuất an toàn tuyệt đối
                $monArr = (array) $mon;
                
                $maMonHoc = $monArr["\0App\Models\MonHoc\0maMon"] ?? $monArr['maMon'] ?? $mon->maMon ?? 'N/A';
                $tenMonHoc = $monArr["\0App\Models\MonHoc\0ten"] ?? $monArr['ten'] ?? $monArr['tenMon'] ?? $mon->ten ?? 'Chưa có tên';
                $tinChiMon = $monArr["\0App\Models\MonHoc\0soTinChi"] ?? $monArr['soTinChi'] ?? $mon->soTinChi ?? 0;
                $monId = $monArr["\0App\Models\MonHoc\0id"] ?? $monArr['id'] ?? $mon->id ?? $id;

                $tongTinChiMon = (int)$tinChiMon * (int)$soLuong;

                $chiTiet[] = [
                    'id' => $monId,
                    'maMon' => $maMonHoc,
                    'tenMon' => $tenMonHoc,
                    'soTinChi' => (int)$tinChiMon,
                    'soLuong' => (int)$soLuong,
                    'tongTinChi' => $tongTinChiMon
                ];

                $tongSoTinChi += $tongTinChiMon;
                $tongSoMon += (int)$soLuong;
            } else {
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