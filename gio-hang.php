<?php
/**
 * Tệp: gio-hang.php
 * Chức năng: Quản lý kế hoạch học tập bằng session.
 * - Thêm môn học.
 * - Cập nhật số lượng từ 1 đến 5.
 * - Xóa một môn.
 * - Xóa toàn bộ kế hoạch.
 * - Kiểm tra dữ liệu ở phía máy chủ và dùng PRG sau POST.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

use App\Services\KeHoachHocTap;

$keHoachService = new KeHoachHocTap();

/*
 * Xử lý form trước khi xuất HTML.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = (string) ($_POST['hanh_dong'] ?? '');
    $trangQuayLai = 'gio-hang.php';

    // Chỉ cho phép quay lại các trang nội bộ đã định trước sau khi thêm môn.
    if ($hanhDong === 'them') {
        $quayLai = (string) ($_POST['quay_lai'] ?? '');

        if (
            $quayLai === 'danh-sach.php'
            || preg_match('/^chi-tiet\\.php\\?id=[1-9][0-9]*$/', $quayLai) === 1
        ) {
            $trangQuayLai = $quayLai;
        }
    }

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $soLuong = filter_var(
        $_POST['so_luong'] ?? null,
        FILTER_VALIDATE_INT
    );

    $thanhCong = false;

    switch ($hanhDong) {
        case 'them':
            if ($id === false || $soLuong === false) {
                $_SESSION['flash_error'] = 'Dữ liệu môn học không hợp lệ.';
                break;
            }

            $thanhCong = $keHoachService->them($id, $soLuong);

            if ($thanhCong) {
                $_SESSION['flash_success'] = 'Môn học đã được thêm vào kế hoạch học tập.';
            } else {
                $_SESSION['flash_error'] =
                    'Không thể thêm môn học. Môn học không tồn tại hoặc dữ liệu không hợp lệ.';
            }
            break;

        case 'cap_nhat':
            if ($id === false || $soLuong === false) {
                $_SESSION['flash_error'] =
                    'Số lượng phải là một giá trị hợp lệ từ 1 đến 5.';
                break;
            }

            $thanhCong = $keHoachService->capNhat($id, $soLuong);

            if ($thanhCong) {
                $_SESSION['flash_success'] = 'Đã cập nhật kế hoạch học tập.';
            } else {
                $_SESSION['flash_error'] =
                    'Không thể cập nhật. Số lượng phải từ 1 đến 5 và môn học phải tồn tại.';
            }
            break;

        case 'xoa':
            if ($id === false) {
                $_SESSION['flash_error'] = 'Mã môn học không hợp lệ.';
                break;
            }

            $thanhCong = $keHoachService->xoa($id);

            if ($thanhCong) {
                $_SESSION['flash_success'] = 'Đã xóa môn học khỏi kế hoạch.';
            } else {
                $_SESSION['flash_error'] = 'Không tìm thấy môn học trong kế hoạch.';
            }
            break;

        case 'xoa_het':
            $keHoachService->xoaTatCa();
            $_SESSION['flash_success'] = 'Đã xóa toàn bộ kế hoạch học tập.';
            break;

        default:
            $_SESSION['flash_error'] = 'Thao tác không hợp lệ.';
            break;
    }

    /*
     * PRG: chuyển hướng sau POST để tránh gửi lại form khi F5.
     */
    header('Location: ' . $trangQuayLai);
    exit;
}

/*
 * Lấy dữ liệu sau khi xử lý POST.
 */
$duLieuGio = $keHoachService->layDanhSachChiTiet();

$danhSachMon = $duLieuGio['danh_sach'];
$soMon = $duLieuGio['tong_so_mon'];
$tongTinChi = $duLieuGio['tong_so_tin_chi'];

// Dữ liệu cho thanh điều hướng dùng chung.
$tieuDe = 'Giỏ hàng';
$trang = 'gio-hang';
$goc = '';

require __DIR__ . '/inc/header.php';
?>

<link rel="stylesheet" href="css/gio-hang.css">

<main class="noi-dung-chinh">
    <h1>Kế hoạch học tập</h1>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <p class="thong-bao thong-bao--loi" role="alert">
            <?= e($_SESSION['flash_error']) ?>
        </p>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <p class="thong-bao thong-bao--thanh-cong" role="status">
            <?= e($_SESSION['flash_success']) ?>
        </p>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (empty($danhSachMon)): ?>
        <div class="empty-cart">
            <p>Chưa có môn nào trong kế hoạch học tập của bạn.</p>

            <p>
                <a href="danh-sach.php" class="btn">
                    Vào danh sách môn học để thêm
                </a>
            </p>
        </div>
    <?php else: ?>

        <div class="cart-summary">
            Tổng số môn đăng ký:
            <strong><?= (int) $soMon ?></strong>
            —
            Tổng số tín chỉ tích lũy:
            <strong><?= (int) $tongTinChi ?></strong>
        </div>

        <table class="table-cart">
            <thead>
                <tr>
                    <th>Mã môn</th>
                    <th>Tên môn học</th>
                    <th>Tín chỉ</th>
                    <th>Số lượng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($danhSachMon as $item): ?>
                    <tr>
                        <td>
                            <code>
                                <?= e((string) ($item['maMon'] ?? '')) ?>
                            </code>
                        </td>

                        <td>
                            <strong>
                                <?= e((string) ($item['tenMon'] ?? '')) ?>
                            </strong>
                        </td>

                        <td>
                            <?= (int) ($item['soTinChi'] ?? 0) ?>
                        </td>

                        <td>
                            <form
                                action="gio-hang.php"
                                method="post"
                                class="form-cap-nhat"
                            >
                                <input
                                    type="hidden"
                                    name="hanh_dong"
                                    value="cap_nhat"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) ($item['id'] ?? 0) ?>"
                                >

                                <input
                                    type="number"
                                    name="so_luong"
                                    value="<?= (int) ($item['soLuong'] ?? 1) ?>"
                                    min="1"
                                    max="5"
                                    class="input-number"
                                    aria-label="Số lượng môn <?= e((string) ($item['tenMon'] ?? '')) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-update"
                                >
                                    Cập nhật
                                </button>
                            </form>
                        </td>

                        <td>
                            <form
                                action="gio-hang.php"
                                method="post"
                                class="form-xoa"
                            >
                                <input
                                    type="hidden"
                                    name="hanh_dong"
                                    value="xoa"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) ($item['id'] ?? 0) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >
                                    Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-actions">
            <form
                action="gio-hang.php"
                method="post"
            >
                <input
                    type="hidden"
                    name="hanh_dong"
                    value="xoa_het"
                >

                <button
                    type="submit"
                    class="btn btn-danger"
                >
                    Xóa hết kế hoạch
                </button>
            </form>
        </div>

    <?php endif; ?>
</main>

<?php
require __DIR__ . '/inc/footer.php';
?>
