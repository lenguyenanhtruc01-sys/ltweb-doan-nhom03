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
    $cssTrang = 'css/don-hang.css';
    require __DIR__ . '/inc/header.php';
    ?>
    <main class="noi-dung-chinh dh-trang">
        <h1 class="dh-tieu-de">Chi tiết kế hoạch #<?= e((int) $keHoach['id']) ?></h1>

        <!-- THÔNG BÁO FLASH -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <p class="dh-thong-bao dh-thong-bao--ok">
                ✔ <?= e($_SESSION['flash_success']) ?>
            </p>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <p class="dh-thong-bao dh-thong-bao--loi">
                ✖ <?= e($_SESSION['flash_error']) ?>
            </p>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="dh-hop">
            <p><strong>Ngày gửi:</strong> <?= e((string) $keHoach['ngay_tao']) ?></p>
            <p><strong>Trạng thái:</strong>
                <span class="dh-nhan"><?= e(KhoKeHoach::nhanTrangThai((string) $keHoach['trang_thai'])) ?></span>
            </p>
            <?php if (!empty($keHoach['ghi_chu'])): ?>
                <p><strong>Ghi chú:</strong> <?= e((string) $keHoach['ghi_chu']) ?></p>
            <?php endif; ?>
        </div>

        <table class="dh-bang">
            <thead>
                <tr>
                    <th>Mã môn</th>
                    <th>Tên môn học</th>
                    <th class="dh-giua">Tín chỉ</th>
                    <th class="dh-giua">Số lượng</th>
                    <th class="dh-giua">Tổng tín chỉ</th>
                </tr>
            </thead>
            <tbody>
            <?php $tong = 0; foreach ($keHoach['chi_tiet'] as $ct):
                $t = (int)$ct['so_tin_chi'] * (int)$ct['so_luong'];
                $tong += $t; ?>
                <tr>
                    <td><code><?= e($ct['ma_mon']) ?></code></td>
                    <td><?= e($ct['ten_mon']) ?></td>
                    <td class="dh-giua"><?= e((int)$ct['so_tin_chi']) ?></td>
                    <td class="dh-giua"><?= e((int)$ct['so_luong']) ?></td>
                    <td class="dh-giua dh-dam"><?= e((int)$t) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p class="dh-tong"><strong>Tổng tín chỉ tích lũy:</strong> <span class="dh-nhan"><?= e((int)$tong) ?></span></p>

        <!-- FORM ĐỔI TRẠNG THÁI (Chỉ hiện với Admin) -->
        <?php if ($laQuanTri): ?>
        <div class="dh-quan-tri">
            <h2>Quản trị viên: Đổi trạng thái</h2>
            <form method="post" class="dh-form-ngang">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e((int)$keHoach['id']) ?>">

                <select name="trang_thai" class="dh-select">
                    <?php foreach (KhoKeHoach::TRANG_THAI_HOP_LE as $tt): ?>
                        <option value="<?= e($tt) ?>" <?= $tt === $keHoach['trang_thai'] ? 'selected' : '' ?>>
                            <?= e(KhoKeHoach::nhanTrangThai($tt)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="dh-nut">
                    Lưu trạng thái
                </button>
            </form>
        </div>
        <?php endif; ?>

        <p class="dh-quay-lai"><a href="don-hang.php" class="dh-lien-ket">&larr; Quay lại danh sách kế hoạch</a></p>
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
$cssTrang  = 'css/don-hang.css';
require __DIR__ . '/inc/header.php';
?>
<main class="noi-dung-chinh dh-trang dh-trang--rong">
    <h1 class="dh-tieu-de"><?= e($tieuDe) ?></h1>

    <?php if (empty($dsKeHoach)): ?>
        <div class="dh-trong">
            <p>Chưa có kế hoạch nào được gửi.</p>
            <a href="danh-sach.php" class="dh-nut--lien-ket">Thêm môn vào giỏ ngay</a>
        </div>
    <?php else: ?>
        <table class="dh-bang dh-bang--danh-sach">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <?php if ($laQuanTri): ?><th>Người gửi</th><?php endif; ?>
                    <th>Ngày gửi</th>
                    <th class="dh-giua">Số môn</th>
                    <th class="dh-giua">Tổng tín chỉ</th>
                    <th>Trạng thái</th>
                    <th class="dh-giua">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsKeHoach as $k): ?>
                <tr>
                    <td class="dh-giua dh-dam">#<?= e((int)$k['id']) ?></td>
                    <?php if ($laQuanTri): ?>
                        <td>
                            <?= e((string) $k['ho_ten']) ?><br>
                            <small class="dh-phu"><?= e((string) $k['email']) ?></small>
                        </td>
                    <?php endif; ?>
                    <td><?= e((string) $k['ngay_tao']) ?></td>
                    <td class="dh-giua"><?= e((int)$k['so_mon']) ?></td>
                    <td class="dh-giua dh-nhan"><?= e((int)$k['tong_tin_chi']) ?></td>
                    <td>
                        <?= e(KhoKeHoach::nhanTrangThai((string) $k['trang_thai'])) ?>
                    </td>
                    <td class="dh-giua">
                        <a href="don-hang.php?id=<?= e((int)$k['id']) ?>" class="dh-lien-ket">Xem chi tiết</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>
