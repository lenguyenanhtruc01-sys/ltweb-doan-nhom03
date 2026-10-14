<?php
/**
 * Tệp: src/Models/MonHoc.php
 * Chức năng: Lớp thực thể đại diện cho một môn học.
 */

namespace App\Models;

class MonHoc
{
    public $id;
    public $maMon;
    public $tenMon;
    public $soTinChi;
    public $diemChu;
    public $diemHe4;
    public $ketQua;
    public $trangThai;
    public $anh;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->maMon = $data['maMon'] ?? $data['ma_mon'] ?? '';
        $this->tenMon = $data['tenMon'] ?? $data['ten_mon'] ?? $data['ten'] ?? '';
        $this->soTinChi = isset($data['soTinChi'])
            ? (int) $data['soTinChi']
            : (int) ($data['tin_chi'] ?? 0);

        $this->diemChu = $data['diemChu'] ?? null;
        $this->diemHe4 = isset($data['diemHe4'])
            ? (float) $data['diemHe4']
            : null;
        $this->ketQua = $data['ketQua'] ?? null;

        $this->trangThai = $data['trangThai'] ?? $data['trang_thai'] ?? '';
        $this->anh = $data['anh'] ?? $data['hinh_anh'] ?? '';
    }
}
