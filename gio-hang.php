<?php
/**
 * Tệp: gio-hang.php
 * Chức năng: Quản lý kế hoạch học tập + Gửi kế hoạch (Chức năng 5).
 * Thử: thêm/sửa/xóa môn và gửi kế hoạch; dữ liệu sai phải báo lỗi, giữ giỏ.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

use App\Services\KeHoachHocTap;

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

    // Dùng cùng token giỏ hàng với API; kiểm tra trước khi đồng bộ hoặc đổi giỏ.
    $token = $_POST['_csrf'] ?? null;
    $tokenPhien = $_SESSION['csrf_gio_hang'] ?? null;
    if (!is_string($token) || !is_string($tokenPhien) || $tokenPhien === ''
        || !hash_equals($tokenPhien, $token)) {
        $_SESSION['flash_error'] = 'Phiên giỏ hàng không hợp lệ. Tải lại trang để thử lại.';
        header('Location: ' . $trangQuayLai, true, 303);
        exit;
    }
    $keHoachService = new KeHoachHocTap();

    $id = soNguyenDauVao($_POST['id'] ?? null);
    $soLuong = soNguyenDauVao($_POST['so_luong'] ?? null, PHP_INT_MIN);

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
$keHoachService = new KeHoachHocTap();
$duLieuGio = $keHoachService->layDanhSachChiTiet();
$danhSachMon = $duLieuGio['danh_sach'];
$soMon = $duLieuGio['tong_so_mon'];
$tongTinChi = $duLieuGio['tong_so_tin_chi'];

$tieuDe = 'Giỏ hàng';
$trang = 'gio-hang';
$goc = '';
$cssTrang = 'css/gio-hang.css';

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <h1>Kế hoạch học tập</h1>

    <!-- THÔNG BÁO -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="thong-bao thong-bao--loi">
            <?= e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="thong-bao thong-bao--thanh-cong">
            <?= e($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (empty($danhSachMon)): ?>
        <div class="empty-cart">
            <p>Chưa có môn nào trong kế hoạch học tập của bạn.</p>
            <a class="btn" href="danh-sach.php">Vào danh sách môn học để thêm</a>
        </div>
    <?php else: ?>

        <div class="cart-summary">
            Tổng số môn: <?= e((int) $soMon) ?> — Tổng tín chỉ: <?= e((int) $tongTinChi) ?>
        </div>

        <!-- DANH SÁCH MÔN -->
        <div class="danh-sach-mon">
            <?php foreach ($danhSachMon as $item): ?>
                <div class="mon-hang">
                    <div class="mon-hang__thong-tin">
                        <strong class="mon-hang__ma"><?= e((string) ($item['maMon'] ?? '')) ?></strong> -
                        <span class="mon-hang__ten"><?= e((string) ($item['tenMon'] ?? '')) ?></span>
                        <span class="mon-hang__tin-chi">(<?= e((int) ($item['soTinChi'] ?? 0)) ?> tín chỉ)</span>
                    </div>

                    <div class="mon-hang__hanh-dong">
                        <!-- Form cập nhật -->
                        <form action="gio-hang.php" method="post" class="form-cap-nhat">
                            <?= csrfField() ?>
                            <input type="hidden" name="hanh_dong" value="cap_nhat">
                            <input type="hidden" name="id" value="<?= e((int) ($item['id'] ?? 0)) ?>">
                            <input type="number" name="so_luong" value="<?= e((int) ($item['soLuong'] ?? 1)) ?>" min="1" max="5" class="input-number">
                            <button type="submit" class="btn btn-update">Cập nhật</button>
                        </form>

                        <!-- Form xóa -->
                        <form action="gio-hang.php" method="post" class="form-xoa">
                            <?= csrfField() ?>
                            <input type="hidden" name="hanh_dong" value="xoa">
                            <input type="hidden" name="id" value="<?= e((int) ($item['id'] ?? 0)) ?>">
                            <button type="submit" class="btn btn-danger">Xóa</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-actions">
            <form action="gio-hang.php" method="post" class="form-xoa">
                <?= csrfField() ?>
                <input type="hidden" name="hanh_dong" value="xoa_het">
                <button type="submit" class="btn btn-danger">Xóa hết kế hoạch</button>
            </form>
        </div>

        <!-- KHỐI GỬI KẾ HOẠCH -->
        <div class="gui-ke-hoach">
            <h2>Gửi kế hoạch học tập</h2>
            <p class="gui-ke-hoach__mo-ta">Kế hoạch sẽ được lưu vào tài khoản và chờ quản trị viên duyệt.</p>

            <?php if (nguoiDung() !== null): ?>
                <form action="dat-hang.php" method="post">
                    <?= csrfField() ?>
                    <div class="gui-ke-hoach__truong">
                        <label for="ghi_chu" class="gui-ke-hoach__nhan">Ghi chú (tuỳ chọn):</label>
                        <textarea id="ghi_chu" name="ghi_chu" rows="3" placeholder="Nhập ghi chú cho kế hoạch của bạn (ví dụ: Kế hoạch học kỳ 1)..." class="gui-ke-hoach__ghi-chu"></textarea>
                    </div>

                    <button type="submit" class="btn-gui">
                        Gửi kế hoạch ngay
                    </button>
                </form>
            <?php else: ?>
                <div class="nhac-dang-nhap">
                    Vui lòng <a href="dang-nhap.php">đăng nhập</a> để gửi kế hoạch học tập.
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>
