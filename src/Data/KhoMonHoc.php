<?php
/**
 * Tệp: src/Data/KhoMonHoc.php
 * Chức năng: Lớp truy cập dữ liệu (Data Access) cho môn học, là nơi duy nhất đọc tệp JSON 
 * (data/mon-hoc.json) để chuyển đổi thành các đối tượng MonHoc, hỗ trợ lấy tất cả, 
 * tìm theo ID và tìm kiếm theo từ khóa.
 */

namespace App\Data;

use App\Models\MonHoc;

class KhoMonHoc {
    private $filePath;

    public function __construct() {
        // Đường dẫn trỏ tới file JSON chứa danh sách môn học
        $this->filePath = __DIR__ . '/../../data/mon-hoc.json';
    }

    // Lấy tất cả môn học
    public function tatCa() {
        if (!file_exists($this->filePath)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->filePath);
        $data = json_decode($jsonContent, true);
        
        $danhSach = [];
        if (is_array($data)) {
            foreach ($data as $item) {
                $danhSach[] = new MonHoc($item);
            }
        }
        return $danhSach;
    }

    // Tìm môn học theo ID
    public function timTheoId($id) {
        $tatCa = $this->tatCa();
        foreach ($tatCa as $mon) {
            if ($mon->id == $id) {
                return $mon;
            }
        }
        return null;
    }

    // Tìm kiếm môn học theo từ khóa (tên hoặc mã môn)
    public function timKiem($tuKhoa) {
        $tatCa = $this->tatCa();
        $ketQua = [];
        $tuKhoa = mb_strtolower(trim($tuKhoa), 'UTF-8');

        foreach ($tatCa as $mon) {
            $ten = mb_strtolower($mon->tenMon, 'UTF-8');
            $ma = mb_strtolower($mon->maMon, 'UTF-8');
            
            if (strpos($ten, $tuKhoa) !== false || strpos($ma, $tuKhoa) !== false) {
                $ketQua[] = $mon;
            }
        }
        
        return $ketQua;
    }
}