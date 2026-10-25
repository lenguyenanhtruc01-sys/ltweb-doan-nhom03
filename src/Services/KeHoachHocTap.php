<?php
/**
 * Tệp: src/Services/KeHoachHocTap.php
 * Chức năng: Khách dùng session; giỏ tài khoản được lưu MySQL.
 * Kiểm tra ID môn học và số lượng ở phía máy chủ.
 * Thử: thêm môn, cập nhật 1-5, nhập 0/-3/1000 và xóa môn.
 */

namespace App\Services;

use App\Data\KhoMonHoc;
use App\Data\KhoTrangThaiNguoiDung;

class KeHoachHocTap
{
    private const SESSION_KEY = 'ke_hoach_dang_ky';

    private KhoMonHoc $khoMonHoc;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Khách dùng session; tài khoản có giỏ riêng được lưu trong MySQL.
        if (!isset($_SESSION[self::SESSION_KEY])
            || !is_array($_SESSION[self::SESSION_KEY])
        ) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $this->khoMonHoc = new KhoMonHoc(db());
        $uid = $_SESSION['uid'] ?? null;
        if (is_int($uid) && $uid > 0) {
            $kho = new KhoTrangThaiNguoiDung(db());
            if (!isset($_SESSION['chu_gio_hang'])) {
                // Chỉ gộp giỏ khách; không ghi lại bản sao cũ của tài khoản.
                $kho->gopGioKhach($uid, $_SESSION[self::SESSION_KEY]);
            }
            $_SESSION[self::SESSION_KEY] = $kho->gioHang($uid);
            $_SESSION['chu_gio_hang'] = $uid;
        }
    }

    private function uid(): ?int
    {
        $uid = $_SESSION['uid'] ?? null;
        return is_int($uid) && $uid > 0 ? $uid : null;
    }

    private function taiLaiGio(): void
    {
        $uid = $this->uid();
        if ($uid !== null) {
            $_SESSION[self::SESSION_KEY] = (new KhoTrangThaiNguoiDung(db()))->gioHang($uid);
        }
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

        if ($monHoc === null || $soLuong > $monHoc->soCho) {
            return false;
        }

        $uid = $this->uid();
        if ($uid !== null) {
            (new KhoTrangThaiNguoiDung(db()))->themMon($uid, $id, $soLuong);
            $this->taiLaiGio();
            return isset($_SESSION[self::SESSION_KEY][$id]);
        }

        // Không cho thêm trùng môn.
        if (isset($_SESSION[self::SESSION_KEY][$id])) {
            return true;
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

        if ($monHoc === null || $soLuong > $monHoc->soCho) {
            return false;
        }

        $uid = $this->uid();
        if ($uid !== null) {
            (new KhoTrangThaiNguoiDung(db()))->capNhatMon($uid, $id, $soLuong);
            $this->taiLaiGio();
            return ($_SESSION[self::SESSION_KEY][$id] ?? null) === $soLuong;
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

        $uid = $this->uid();
        if ($uid !== null) {
            $daXoa = (new KhoTrangThaiNguoiDung(db()))->xoaMon($uid, $id);
            $this->taiLaiGio();
            return $daXoa;
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
        $uid = $this->uid();
        if ($uid !== null) (new KhoTrangThaiNguoiDung(db()))->xoaGioHang($uid);
        $_SESSION[self::SESSION_KEY] = [];
    }

    /**
     * Lấy danh sách môn trong kế hoạch
     * và tính tổng số môn, tổng tín chỉ.
     */
    public function layDanhSachChiTiet(): array
    {
        $this->taiLaiGio();
        $chiTiet = [];
        $tongSoTinChi = 0;
        $tongSoMon = 0;

        $ids = [];
        foreach ($_SESSION[self::SESSION_KEY] as $id => $soLuong) {
            if ((is_int($id) || is_string($id)) && ctype_digit((string) $id)
                && (int) $id > 0 && is_int($soLuong) && $soLuong >= 1 && $soLuong <= 5) {
                $ids[] = (int) $id;
            } else {
                unset($_SESSION[self::SESSION_KEY][$id]);
            }
        }
        $cacMon = $this->khoMonHoc->theoNhieuId($ids);

        foreach ($_SESSION[self::SESSION_KEY] as $id => $soLuong) {
            $id = (int) $id;
            $soLuong = (int) $soLuong;

            $mon = $cacMon[$id] ?? null;

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
