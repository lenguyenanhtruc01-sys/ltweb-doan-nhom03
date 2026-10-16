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
    private string $filePath;

    public function __construct() {
        // Đường dẫn trỏ tới file JSON chứa danh sách môn học
        $this->filePath = __DIR__ . '/../../data/mon-hoc.json';
    }

    // Lấy tất cả môn học
    public function tatCa(): array {
        if (!is_file($this->filePath)) {
            throw new \RuntimeException('Không tìm thấy tệp dữ liệu môn học.');
        }

        if (!is_readable($this->filePath)) {
            throw new \RuntimeException('Không thể đọc tệp dữ liệu môn học.');
        }

        $jsonContent = file_get_contents($this->filePath);

        if ($jsonContent === false) {
            throw new \RuntimeException('Không thể đọc nội dung tệp dữ liệu môn học.');
        }

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $loi) {
            throw new \RuntimeException('Tệp dữ liệu môn học chứa JSON không hợp lệ.', 0, $loi);
        }

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Dữ liệu môn học phải là một mảng JSON.');
        }

        $danhSach = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Mỗi môn học trong dữ liệu phải là một đối tượng JSON.');
            }

            $danhSach[] = new MonHoc($item);
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
