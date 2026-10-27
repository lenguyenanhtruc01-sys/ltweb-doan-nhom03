<?php
/**
 * Tệp: don-hang.php
 * Chức năng 5: Danh sách + Chi tiết kế hoạch học tập.
 * - Khách hàng chỉ xem được đơn của mình (WHERE nguoi_dung_id = ?).
 * - Admin xem được tất cả và đổi trạng thái (POST + CSRF + rowCount).
 * Thử: khách xem đơn của mình, đổi ID của người khác và admin cập nhật trạng thái.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoKeHoach;

// 1. Kiểm tra đăng nhập
$nd        = yeuCauDangNhap();
$uid       = (int) $nd['id'];
$laQuanTri = ($nd['vai_tro'] === 'quan_tri');
$kho       = new KhoKeHoach(db());

// ==========================================
// XỬ LÝ POST: ĐỔI TRẠNG THÁI (Chỉ Admin)
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Chặn nếu không phải admin
    if (!$laQuanTri) {
        yeuCauQuyen('quan_tri');
    }
    $token = $_POST['_csrf'] ?? null;
    if (!is_string($token) || !csrfKiemTra($token)) {
        $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn.';
        header('Location: don-hang.php', true, 303);
        exit;
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tt = is_string($_POST['trang_thai'] ?? null) ? $_POST['trang_thai'] : '';

    if ($id === false || !in_array($tt, KhoKeHoach::TRANG_THAI_HOP_LE, true)) {
        $_SESSION['flash_error'] = 'Dữ liệu đổi trạng thái không hợp lệ.';
        header('Location: don-hang.php', true, 303);
        exit;
    }

    try {
        // KIỂM TRA KẾT QUẢ TRẢ VỀ CỦA HÀM (rowCount)
        $ketQua = $kho->doiTrangThai((int) $id, $tt);
        
        if ($ketQua) {
            $_SESSION['flash_success'] = 'Đã cập nhật trạng thái thành công.';
        } else {
            // Trường hợp rowCount() !== 1 (ID không tồn tại hoặc trạng thái không đổi)
            $_SESSION['flash_error'] = 'Không thể cập nhật: Đơn không tồn tại hoặc trạng thái không thay đổi.';
        }
    } catch (Throwable $e) {
        error_log('don-hang: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Không cập nhật được trạng thái.';
    }
    
    header('Location: don-hang.php?id=' . (int) $id, true, 303);
    exit;
}

// ==========================================
// XỬ LÝ GET: XEM CHI TIẾT HOẶC DANH SÁCH
// ==========================================
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id !== false) {
    // ----- TRANG CHI TIẾT -----
    $keHoach = $kho->chiTiet($id, $uid, $laQuanTri);
    
    // Nếu không có quyền xem (hoặc ID không tồn tại) -> 404
    if ($keHoach === null) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

    $tieuDe = 'Chi tiết kế hoạch #' . (int) $keHoach['id'];
    $trang  = 'don-hang';
    require __DIR__ . '/inc/header.php';
    ?>
    <main class="noi-dung-chinh" style="padding: 20px; max-width: 900px; margin: 0 auto; font-family: Arial, sans-serif;">
        <h1 style="color: #1a365d; margin-bottom: 20px;">Chi tiết kế hoạch #<?= e((int) $keHoach['id']) ?></h1>

        <!-- THÔNG BÁO FLASH -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <p style="color: #155724; background-color: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✔ <?= e($_SESSION['flash_success']) ?>
            </p>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <p style="color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                ✖ <?= e($_SESSION['flash_error']) ?>
            </p>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 5px solid #0d6efd; margin-bottom: 20px;">
            <p style="margin: 5px 0;"><strong>Ngày gửi:</strong> <?= e((string) $keHoach['ngay_tao']) ?></p>
            <p style="margin: 5px 0;"><strong>Trạng thái:</strong> 
                <span style="font-weight: bold; color: #0d6efd;"><?= e(KhoKeHoach::nhanTrangThai((string) $keHoach['trang_thai'])) ?></span>
            </p>
            <?php if (!empty($keHoach['ghi_chu'])): ?>
                <p style="margin: 5px 0;"><strong>Ghi chú:</strong> <?= e((string) $keHoach['ghi_chu']) ?></p>
            <?php endif; ?>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: white;">
            <thead>
                <tr style="background: #1a365d; color: white;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Mã môn</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Tên môn học</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Tín chỉ</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Số lượng</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Tổng tín chỉ</th>
                </tr>
            </thead>
            <tbody>
            <?php $tong = 0; foreach ($keHoach['chi_tiet'] as $ct): 
                $t = (int)$ct['so_tin_chi'] * (int)$ct['so_luong']; 
                $tong += $t; ?>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;"><code><?= e($ct['ma_mon']) ?></code></td>
                    <td style="padding: 12px; border: 1px solid #ddd;"><?= e($ct['ten_mon']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?= e((int)$ct['so_tin_chi']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?= e((int)$ct['so_luong']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold;"><?= e((int)$t) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="text-align: right; font-size: 1.2rem;"><strong>Tổng tín chỉ tích lũy:</strong> <span style="color: #0d6efd; font-weight: bold;"><?= e((int)$tong) ?></span></p>

        <!-- FORM ĐỔI TRẠNG THÁI (Chỉ hiện với Admin) -->
        <?php if ($laQuanTri): ?>
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px;">
            <h2 style="margin-top: 0; color: #1a365d;">Quản trị viên: Đổi trạng thái</h2>
            <form method="post" style="display: flex; gap: 15px; align-items: center;">
                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= e((int)$keHoach['id']) ?>">
                
                <select name="trang_thai" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
                    <?php foreach (KhoKeHoach::TRANG_THAI_HOP_LE as $tt): ?>
                        <option value="<?= e($tt) ?>" <?= $tt === $keHoach['trang_thai'] ? 'selected' : '' ?>>
                            <?= e(KhoKeHoach::nhanTrangThai($tt)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" style="background: #0d6efd; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    Lưu trạng thái
                </button>
            </form>
        </div>
        <?php endif; ?>

        <p style="margin-top: 30px;"><a href="don-hang.php" style="color: #0d6efd; text-decoration: none; font-weight: bold;">&larr; Quay lại danh sách kế hoạch</a></p>
    </main>
    <?php 
    require __DIR__ . '/inc/footer.php'; 
    exit;
} 

// ==========================================
// TRANG DANH SÁCH KẾ HOẠCH
// ==========================================
$dsKeHoach = $laQuanTri ? $kho->dsTatCa() : $kho->dsCuaToi($uid);
$tieuDe    = $laQuanTri ? 'Tất cả kế hoạch' : 'Kế hoạch của tôi';
$trang     = 'don-hang';
require __DIR__ . '/inc/header.php';
?>
<main class="noi-dung-chinh" style="padding: 20px; max-width: 1000px; margin: 0 auto; font-family: Arial, sans-serif;">
    <h1 style="color: #1a365d; margin-bottom: 20px;"><?= e($tieuDe) ?></h1>

    <?php if (empty($dsKeHoach)): ?>
        <div style="padding: 30px; background: #f8f9fa; border-radius: 8px; text-align: center; border: 1px dashed #ccc;">
            <p style="font-size: 1.1rem; color: #666;">Chưa có kế hoạch nào được gửi.</p>
            <a href="danh-sach.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Thêm môn vào giỏ ngay</a>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <thead>
                <tr style="background: #1a365d; color: white;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Mã đơn</th>
                    <?php if ($laQuanTri): ?><th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Người gửi</th><?php endif; ?>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Ngày gửi</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Số môn</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Tổng tín chỉ</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Trạng thái</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsKeHoach as $k): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold;">#<?= e((int)$k['id']) ?></td>
                    <?php if ($laQuanTri): ?>
                        <td style="padding: 12px; border: 1px solid #ddd;">
                            <?= e((string) $k['ho_ten']) ?><br>
                            <small style="color: #666;"><?= e((string) $k['email']) ?></small>
                        </td>
                    <?php endif; ?>
                    <td style="padding: 12px; border: 1px solid #ddd;"><?= e((string) $k['ngay_tao']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?= e((int)$k['so_mon']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #0d6efd; font-weight: bold;"><?= e((int)$k['tong_tin_chi']) ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <?= e(KhoKeHoach::nhanTrangThai((string) $k['trang_thai'])) ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                        <a href="don-hang.php?id=<?= e((int)$k['id']) ?>" style="color: #0d6efd; text-decoration: none; font-weight: bold;">Xem chi tiết</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>
