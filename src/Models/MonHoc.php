<?php
/**
 * Tệp: src/Models/MonHoc.php
 * Chức năng: Lớp thực thể (Entity) đại diện cho một môn học, khởi tạo các thuộc tính 
 * từ mảng dữ liệu (đọc từ tệp JSON hoặc cơ sở dữ liệu).
 */

namespace App\Models;

class MonHoc {
    public $id;
    public $maMon;
    public $tenMon;
    public $soTinChi;
    public $diemChu;
    public $diemHe4;
    public $ketQua;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->maMon = $data['maMon'] ?? '';
        // Hỗ trợ linh hoạt cả khóa 'tenMon' và 'ten' từ dữ liệu JSON gốc
        $this->tenMon = $data['tenMon'] ?? $data['ten'] ?? ''; 
        $this->soTinChi = isset($data['soTinChi']) ? (int)$data['soTinChi'] : 0;
        $this->diemChu = $data['diemChu'] ?? null;
        $this->diemHe4 = isset($data['diemHe4']) ? (float)$data['diemHe4'] : null;
        $this->ketQua = $data['ketQua'] ?? null;
    }
}