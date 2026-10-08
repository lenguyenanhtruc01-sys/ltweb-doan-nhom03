<?php
namespace App\Models;

class MonHoc {
    public $id;
    public $maMon;
    public $tenMon;
    public $soTinChi;
    public $diemChu;
    public $diemHe4;
    public $ketQua;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->maMon = $data['maMon'] ?? '';
        $this->tenMon = $data['tenMon'] ?? '';
        $this->soTinChi = $data['soTinChi'] ?? 0;
        $this->diemChu = $data['diemChu'] ?? '';
        $this->diemHe4 = $data['diemHe4'] ?? 0.0;
        $this->ketQua = $data['ketQua'] ?? '';
    }
}