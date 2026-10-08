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
        // Ánh xạ khóa 'ten' (hoặc 'tenMon') từ JSON vào thuộc tính tenMon của đối tượng
        $this->tenMon = $data['tenMon'] ?? $data['ten'] ?? ''; 
        $this->soTinChi = $data['soTinChi'] ?? 0;
        // Các thuộc tính khác nếu có...
    }
}