<?php
declare(strict_types=1);

/**
 * Nâng cấp bảng kỹ năng/thời khóa biểu của Uyên và bổ sung dữ liệu mẫu thiếu.
 * Đổi tên bảng cũ trước khi tạo bảng mới; giữ ID, dữ liệu riêng và khóa ngoại.
 * Thử: php kiemtra/kiem-thu-doi-ten-bang-uyen.php; chạy bộ cài sau khi lấy code mới.
 */
function doiTenBangCaNhanUyen(PDO $pdo): bool
{
    $cacBang = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $doiTen = [];
    foreach (['tv_uyen_ky_nang'=>'tv_ky_nang', 'tv_uyen_thoi_khoa_bieu'=>'tv_thoi_khoa_bieu'] as $cu=>$moi) {
        if (!in_array($cu, $cacBang, true)) continue;
        if (in_array($moi, $cacBang, true)) {
            throw new RuntimeException('Đồng thời tồn tại ' . $cu . ' và ' . $moi
                . '; giữ nguyên dữ liệu, cần đối chiếu hai bảng trước khi nâng cấp.');
        }
        $doiTen[] = '`' . $cu . '` TO `' . $moi . '`';
    }
    // Kiểm tra tất cả cặp trước khi đổi bất kỳ bảng nào.
    if ($doiTen === []) return false;
    $pdo->exec('RENAME TABLE ' . implode(', ', $doiTen));
    return true;
}

function nangCapCaNhanUyen(PDO $pdo): void
{
    $pdo->exec("INSERT INTO thanh_vien (mssv, ho_ten)
        VALUES ('3120224174', 'Huỳnh Phương Uyên')
        ON DUPLICATE KEY UPDATE ho_ten = VALUES(ho_ten)");

    $kyNang = $pdo->prepare('INSERT INTO tv_ky_nang (mssv, ten, nhom) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE id = id');
    foreach ([
        ['HTML5', 'web'],
        ['Thiết kế giao diện website', 'web'],
        ['Làm việc nhóm', 'lam-viec'],
        ['Quản lý mã nguồn với GitHub', 'lam-viec'],
        ['Thiết kế và quản lý cơ sở dữ liệu', 'du-lieu'],
    ] as [$ten, $nhom]) {
        $kyNang->execute(['3120224174', $ten, $nhom]);
    }

    $monHoc = $pdo->prepare('INSERT INTO tv_thoi_khoa_bieu
        (mssv, ma_mon, ten_mon, ma_lop, thu, tiet_bat_dau, tiet_ket_thuc, phong)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE id = id');
    foreach ([
        ['31231755', 'Thiết kế và lập trình web', '24-0102', 8, 1, 3, 'B3-303'],
        ['31231330', 'Khai phá dữ liệu', '24-0102', 5, 4, 6, 'A5-404B'],
        ['31241283', 'Hệ quản trị cơ sở dữ liệu', '24-0301', 4, 7, 10, 'B1-104'],
        ['31231016', 'Công nghệ phần mềm', '24-0103', 5, 10, 12, 'B3-304'],
        ['21221904', 'Lịch sử Đảng Cộng sản Việt Nam', '24-0318', 2, 11, 12, 'A6-502'],
        ['31221010', 'An toàn thông tin', '24-0103', 7, 11, 12, 'B3-506'],
    ] as [$maMon, $tenMon, $maLop, $thu, $tietBatDau, $tietKetThuc, $phong]) {
        $monHoc->execute([
            '3120224174', $maMon, $tenMon, $maLop, $thu, $tietBatDau, $tietKetThuc, $phong,
        ]);
    }
}
