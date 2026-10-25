<?php
declare(strict_types=1);

/**
 * Kiểm tra dữ liệu dùng chung cho thêm và sửa môn học.
 * Không ép mảng thành chuỗi; danh mục phải thuộc tập lấy từ CSDL.
 * Thử mã rỗng/trùng, tên quá dài, số âm, danh mục giả và đầu vào dạng mảng.
 */
namespace App\Services;

final class KiemTraMonHoc
{
    public static function kiemTra(array $vao, array $danhMuc): array
    {
        $d = []; $loi = [];
        foreach (['ma_mon', 'ten_mon', 'mo_ta', 'danh_muc_id', 'so_tin_chi', 'so_cho'] as $cot) {
            $d[$cot] = is_string($vao[$cot] ?? null) ? trim($vao[$cot]) : '';
        }
        $d['ma_mon'] = strtoupper($d['ma_mon']);
        if (preg_match('/\A[A-Z0-9][A-Z0-9_-]{0,19}\z/D', $d['ma_mon']) !== 1) {
            $loi[] = 'Mã môn gồm 1–20 chữ cái không dấu, số, gạch ngang hoặc gạch dưới.';
        }
        if ($d['ten_mon'] === '' || mb_strlen($d['ten_mon']) > 200) $loi[] = 'Tên môn phải có 1–200 ký tự.';
        if (mb_strlen($d['mo_ta']) > 10000) $loi[] = 'Mô tả tối đa 10.000 ký tự.';
        $dm = filter_var($d['danh_muc_id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($dm === false || !in_array($dm, array_map('intval', array_column($danhMuc, 'id')), true)) {
            $loi[] = 'Vui lòng chọn danh mục hợp lệ.';
        }
        $tc = filter_var($d['so_tin_chi'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>30]]);
        $cho = filter_var($d['so_cho'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>1000000]]);
        if ($tc === false) $loi[] = 'Số tín chỉ phải là số nguyên từ 1 đến 30.';
        if ($cho === false) $loi[] = 'Số chỗ còn lại phải là số nguyên từ 0 đến 1.000.000.';
        // Giữ dữ liệu nhập sai để người dùng sửa; chỉ chuyển số khi đã hợp lệ.
        if ($dm !== false) $d['danh_muc_id'] = $dm;
        if ($tc !== false) $d['so_tin_chi'] = $tc;
        if ($cho !== false) $d['so_cho'] = $cho;
        return [$d, $loi];
    }
}
