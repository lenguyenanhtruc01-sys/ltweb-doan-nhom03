<?php
/**
 * Tệp: gio-hang.php
 * Chức năng: Quản lý kế hoạch học tập + Gửi kế hoạch (Chức năng 5).
 * Thử: thêm/sửa/xóa môn và gửi kế hoạch; dữ liệu sai phải báo lỗi, giữ giỏ.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

// Định nghĩa hàm CSRF dự phòng
if (!function_exists('csrfToken')) {
    function csrfToken(): string {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}
if (!function_exists('csrfKiemTra')) {
    function csrfKiemTra(?string $token): bool {
        return is_string($token) && $token !== '' && isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

use App\Services\KeHoachHocTap;

$keHoachService = new KeHoachHocTap();

/* Xử lý form POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = layChuoiDauVao($_POST['hanh_dong'] ?? '');
    $trangQuayLai = 'gio-hang.php';

    if ($hanhDong === 'them') {
        $quayLai = layChuoiDauVao($_POST['quay_lai'] ?? '');
        if ($quayLai === 'danh-sach.php' || preg_match('/^chi-tiet\.php\?id=[1-9][0-9]*$/', $quayLai) === 1) {
            $trangQuayLai = $quayLai;
        }
    }

    $idDauVao = $_POST['id'] ?? null;
    $id = (is_string($idDauVao) || is_int($idDauVao))
        ? filter_var($idDauVao, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;

    $soLuongDauVao = $_POST['so_luong'] ?? null;
    $soLuong = (is_string($soLuongDauVao) || is_int($soLuongDauVao))
        ? filter_var($soLuongDauVao, FILTER_VALIDATE_INT)
        : false;

    $thanhCong = false;

    switch ($hanhDong) {
        case 'them':
            if ($id === false || $soLuong === false) {
                $_SESSION['flash_error'] = 'Dữ liệu môn học không hợp lệ.';
                break;
            }
            $thanhCong = $keHoachService->them($id, $soLuong);
            $_SESSION[$thanhCong ? 'flash_success' : 'flash_error'] =
                $thanhCong ? 'Môn học đã được thêm vào kế hoạch học tập.'
                           : 'Không thể thêm môn học.';
            break;

        case 'cap_nhat':
            if ($id === false || $soLuong === false) {
                $_SESSION['flash_error'] = 'Số lượng phải là một giá trị hợp lệ từ 1 đến 5.';
                break;
            }
            $thanhCong = $keHoachService->capNhat($id, $soLuong);
            $_SESSION[$thanhCong ? 'flash_success' : 'flash_error'] =
                $thanhCong ? 'Đã cập nhật kế hoạch học tập.'
                           : 'Không thể cập nhật.';
            break;

        case 'xoa':
            if ($id === false) {
                $_SESSION['flash_error'] = 'Mã môn học không hợp lệ.';
                break;
            }
            $thanhCong = $keHoachService->xoa($id);
            $_SESSION[$thanhCong ? 'flash_success' : 'flash_error'] =
                $thanhCong ? 'Đã xóa môn học khỏi kế hoạch.'
                           : 'Không tìm thấy môn học trong kế hoạch.';
            break;

        case 'xoa_het':
            $keHoachService->xoaTatCa();
            $_SESSION['flash_success'] = 'Đã xóa toàn bộ kế hoạch học tập.';
            break;

        default:
            $_SESSION['flash_error'] = 'Thao tác không hợp lệ.';
            break;
    }

    header('Location: ' . $trangQuayLai);
    exit;
}

/* Lấy dữ liệu xuất HTML */
$duLieuGio = $keHoachService->layDanhSachChiTiet();
$danhSachMon = $duLieuGio['danh_sach'];
$soMon = $duLieuGio['tong_so_mon'];
$tongTinChi = $duLieuGio['tong_so_tin_chi'];

$tieuDe = 'Giỏ hàng';
$trang = 'gio-hang';
$goc = '';

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh" style="padding: 20px; max-width: 900px; margin: 0 auto; font-family: Arial, sans-serif;">
    <h1 style="color: #1a365d; margin-bottom: 20px;">Kế hoạch học tập</h1>

    <!-- THÔNG BÁO -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div style="color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?= e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div style="color: #155724; background-color: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?= e($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (empty($danhSachMon)): ?>
        <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ccc;">
            <p style="font-size: 1.1rem; color: #666; margin-bottom: 20px;">Chưa có môn nào trong kế hoạch học tập của bạn.</p>
            <a href="danh-sach.php" style="display: inline-block; background: #0d6efd; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Vào danh sách môn học để thêm</a>
        </div>
    <?php else: ?>

        <div style="background: #e2e8f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; color: #1a365d;">
            Tổng số môn: <?= (int) $soMon ?> — Tổng tín chỉ: <?= (int) $tongTinChi ?>
        </div>

        <!-- DANH SÁCH MÔN -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
            <?php foreach ($danhSachMon as $item): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    
                    <div style="flex: 1; font-size: 1.05rem;">
                        <strong style="color: #0d6efd;"><?= e((string) ($item['maMon'] ?? '')) ?></strong> - 
                        <span style="color: #333;"><?= e((string) ($item['tenMon'] ?? '')) ?></span> 
                        <span style="color: #666; font-size: 0.9rem;">(<?= (int) ($item['soTinChi'] ?? 0) ?> tín chỉ)</span>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center;">
                        <!-- Form cập nhật -->
                        <form action="gio-hang.php" method="post" style="margin: 0; display: flex; gap: 5px;">
                            <input type="hidden" name="hanh_dong" value="cap_nhat">
                            <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                            <input type="number" name="so_luong" value="<?= (int) ($item['soLuong'] ?? 1) ?>" min="1" max="5" style="width: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
                            <!-- Đã sửa lại màu chữ nút Cập nhật -->
                            <button type="submit" style="padding: 6px 12px; background: #e2e8f0; color: #000; font-weight: bold; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">Cập nhật</button>
                        </form>

                        <!-- Form xóa -->
                        <form action="gio-hang.php" method="post" style="margin: 0;">
                            <input type="hidden" name="hanh_dong" value="xoa">
                            <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                            <button type="submit" style="padding: 6px 12px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">Xóa</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: right; margin-bottom: 40px;">
            <form action="gio-hang.php" method="post" style="margin: 0;">
                <input type="hidden" name="hanh_dong" value="xoa_het">
                <button type="submit" style="padding: 10px 15px; background: #dc2626; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Xóa hết kế hoạch</button>
            </form>
        </div>

        <!-- KHỐI GỬI KẾ HOẠCH -->
        <div style="border: 1px solid #b6d4fe; border-left: 5px solid #0d6efd; border-radius: 8px; padding: 25px; background: #f8f9fa; margin-top: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2 style="color: #0d6efd; margin-top: 0; font-size: 1.5rem; margin-bottom: 10px;">Gửi kế hoạch học tập</h2>
            <p style="color: #555; margin-bottom: 20px; font-size: 0.95rem;">Kế hoạch sẽ được lưu vào tài khoản và chờ quản trị viên duyệt.</p>
            
            <?php if (nguoiDung() !== null): ?>
                
                <form action="dat-hang.php" method="post" style="margin: 0;">
                    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 8px; color: #333;">Ghi chú (tuỳ chọn):</label>
                        <textarea name="ghi_chu" rows="3" placeholder="Nhập ghi chú cho kế hoạch của bạn (ví dụ: Kế hoạch học kỳ 1)..." style="width: 100%; max-width: 600px; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" style="background-color: #0d6efd; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background-color 0.2s;">
                        Gửi kế hoạch ngay
                    </button>
                </form>

            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba;">
                    Vui lòng <a href="dang-nhap.php" style="font-weight: 600; color: #0d6efd; text-decoration: underline;">đăng nhập</a> để gửi kế hoạch học tập.
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>