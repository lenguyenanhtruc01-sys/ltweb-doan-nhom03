<?php
/**
 * Tệp: src/Models/MonHoc.php
 * Chức năng: Lớp thực thể đại diện cho một môn học.
 */

namespace App\Models;

class MonHoc implements \JsonSerializable
{
    /** Chỉ trả các trường công khai; không lộ mô tả/cột nội bộ từ SELECT *. */
    public function jsonSerialize(): array
    {
        return ['id' => (int) $this->id, 'maMon' => $this->maMon,
            'tenMon' => $this->tenMon, 'soTinChi' => $this->soTinChi,
            'anh' => $this->anh, 'trangThai' => $this->trangThai];
    }

    public $id;
    public $maMon;
    public $tenMon;
    public $soTinChi;
    public $diemChu;
    public $diemHe4;
    public $ketQua;
    public $trangThai;
    public $anh;
    public int $soCho;
    public $danhMucId;
    public $tenDanhMuc;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->soCho = (int) ($data['so_cho'] ?? 0);
        $this->maMon = $data['maMon'] ?? $data['ma_mon'] ?? '';
        $this->tenMon = $data['tenMon'] ?? $data['ten_mon'] ?? $data['ten'] ?? '';
        $this->soTinChi = isset($data['soTinChi'])
            ? (int) $data['soTinChi']
            : (int) ($data['so_tin_chi'] ?? $data['tin_chi'] ?? 0);

        $this->diemChu = $data['diemChu'] ?? null;
        $this->diemHe4 = isset($data['diemHe4'])
            ? (float) $data['diemHe4']
            : null;
        $this->ketQua = $data['ketQua'] ?? null;

        $this->trangThai = $data['trangThai'] ?? $data['trang_thai'] ?? '';
        $this->anh = $data['anh'] ?? $data['hinh_anh'] ?? '';
        $this->danhMucId = isset($data['danhMucId'])
            ? (int) $data['danhMucId']
            : (int) ($data['danh_muc_id'] ?? 0);
        $this->tenDanhMuc = $data['tenDanhMuc'] ?? $data['ten_danh_muc'] ?? '';
    }
}
