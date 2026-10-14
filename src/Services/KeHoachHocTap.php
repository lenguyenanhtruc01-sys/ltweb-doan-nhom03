<?php
/**
 * Tệp: src/Services/KeHoachHocTap.php
 * Chức năng: Quản lý kế hoạch học tập bằng session.
 * Kiểm tra ID môn học và số lượng ở phía máy chủ.
 * Thử: thêm môn, cập nhật 1-5, nhập 0/-3/1000 và xóa môn.
 */

namespace App\Services;

use App\Data\KhoMonHoc;

class KeHoachHocTap
{
    private const SESSION_KEY = 'ke_hoach_dang_ky';

    private KhoMonHoc $khoMonHoc;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])
            || !is_array($_SESSION[self::SESSION_KEY])
        ) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $this->khoMonHoc = new KhoMonHoc();
    }

    /**
     * Thêm một môn học vào kế hoạch.
     */
    public function them(int $id, int $soLuong = 1): bool
    {
        if ($id <= 0 || $soLuong < 1 || $soLuong > 5) {
            return false;
        }

        $monHoc = $this->khoMonHoc->timTheoId($id);

        if ($monHoc === null) {
            return false;
        }

        // Không cho thêm trùng môn.
        if (isset($_SESSION[self::SESSION_KEY][$id])) {
            return false;
        }

        $_SESSION[self::SESSION_KEY][$id] = $soLuong;

        return true;
    }

    /**
     * Cập nhật số lượng môn.
     * Chỉ chấp nhận từ 1 đến 5.
     */
    public function capNhat(int $id, int $soLuong): bool
    {
        if ($id <= 0 || $soLuong < 1 || $soLuong > 5) {
            return false;
        }

        $monHoc = $this->khoMonHoc->timTheoId($id);

        if ($monHoc === null) {
            return false;
        }

        if (!isset($_SESSION[self::SESSION_KEY][$id])) {
            return false;
        }

        $_SESSION[self::SESSION_KEY][$id] = $soLuong;

        return true;
    }

    /**
     * Xóa một môn khỏi kế hoạch.
     */
    public function xoa(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        if (!isset($_SESSION[self::SESSION_KEY][$id])) {
            return false;
        }

        unset($_SESSION[self::SESSION_KEY][$id]);

        return true;
    }

    /**
     * Xóa toàn bộ kế hoạch học tập.
     */
    public function xoaTatCa(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
    }

    /**
     * Lấy danh sách môn trong kế hoạch
     * và tính tổng số môn, tổng tín chỉ.
     */
    public function layDanhSachChiTiet(): array
    {
        $chiTiet = [];
        $tongSoTinChi = 0;
        $tongSoMon = 0;

        foreach ($_SESSION[self::SESSION_KEY] as $id => $soLuong) {
            $id = (int) $id;
            $soLuong = (int) $soLuong;

            $mon = $this->khoMonHoc->timTheoId($id);

            // Nếu dữ liệu session chứa ID không còn tồn tại,
            // tự loại bỏ khỏi kế hoạch.
            if ($mon === null) {
                unset($_SESSION[self::SESSION_KEY][$id]);
                continue;
            }

            // Session có thể bị chỉnh sửa nên kiểm tra lại số lượng.
            if ($soLuong < 1 || $soLuong > 5) {
                unset($_SESSION[self::SESSION_KEY][$id]);
                continue;
            }

            $tongTinChiMon = $mon->soTinChi * $soLuong;

            $chiTiet[] = [
                'id' => $mon->id,
                'maMon' => $mon->maMon,
                'tenMon' => $mon->tenMon,
                'soTinChi' => $mon->soTinChi,
                'soLuong' => $soLuong,
                'tongTinChi' => $tongTinChiMon,
            ];

            $tongSoTinChi += $tongTinChiMon;
            $tongSoMon += $soLuong;
        }

        return [
            'danh_sach' => $chiTiet,
            'tong_so_mon' => $tongSoMon,
            'tong_so_tin_chi' => $tongSoTinChi,
        ];
    }
}
