<?php
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

use App\Data\KhoKeHoach;

$nd        = yeuCauDangNhap();
$uid       = (int) $nd['id'];
$laQuanTri = ($nd['vai_tro'] === 'quan_tri');
$kho       = new KhoKeHoach(db());

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$laQuanTri) {
        yeuCauQuyen('quan_tri');
    }
    if (!csrfKiemTra($_POST['_csrf'] ?? null)) {
        $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn.';
        header('Location: don-hang.php', true, 303);
        exit;
    }

    $idRaw = $_POST['id'] ?? '';
    $id    = is_string($idRaw) || is_int($idRaw)
        ? filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;

    $ttRaw = $_POST['trang_thai'] ?? '';
    $tt    = is_string($ttRaw) ? $ttRaw : '';

    if ($id === false || !in_array($tt, KhoKeHoach::TRANG_THAI_HOP_LE, true)) {
        $_SESSION['flash_error'] = 'Dữ liệu đổi trạng thái không hợp lệ.';
        header('Location: don-hang.php', true, 303);
        exit;
    }

    try {
        $kho->doiTrangThai((int) $id, $tt);
        $_SESSION['flash_success'] = 'Đã cập nhật trạng thái.';
    } catch (Throwable $e) {
        error_log('don-hang: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Không cập nhật được trạng thái.';
    }
    header('Location: don-hang.php?id=' . (int) $id, true, 303);
    exit;
}

$idRaw = $_GET['id'] ?? '';
$id    = is_string($idRaw) || is_int($idRaw)
    ? filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;

if ($id !== false && $id > 0) {
    $keHoach = $kho->chiTiet((int) $id, $uid, $laQuanTri);
    if ($keHoach === null) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

    $tieuDe = 'Chi tiết kế hoạch #' . (int) $keHoach['id'];
    $trang  = 'don-hang';
    require __DIR__ . '/inc/header.php';
    ?>
    <main class="noi-dung-chinh">
        <h1>Chi tiết kế hoạch #<?= (int) $keHoach['id'] ?></h1>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <p class="thong-bao thong-bao--thanh-cong" role="status"><?= e($_SESSION['flash_success']) ?></p>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <p class="thong-bao thong-bao--loi" role="alert"><?= e($_SESSION['flash_error']) ?></p>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <p><strong>Ngày gửi:</strong> <?= e((string) $keHoach['ngay_tao']) ?></p>
        <p><strong>Trạng thái:</strong> <?= e(KhoKeHoach::nhanTrangThai((string) $keHoach['trang_thai'])) ?></p>
        <?php if (!empty($keHoach['ghi_chu'])): ?>
            <p><strong>Ghi chú:</strong> <?= e((string) $keHoach['ghi_chu']) ?></p>
        <?php endif; ?>

        <table class="table-cart">
            <thead>
                <tr>
                    <th>Mã môn</th><th>Tên môn</th><th>Tín chỉ</th><th>Số lượng</th><th>Tổng tín chỉ</th>
                </tr>
            </thead>
            <tbody>
            <?php $tong = 0; foreach ($keHoach['chi_tiet'] as $ct):
                $t = (int) $ct['so_tin_chi'] * (int) $ct['so_luong'];
                $tong += $t; ?>
                <tr>
                    <td><code><?= e((string) $ct['ma_mon']) ?></code></td>
                    <td><?= e((string) $ct['ten_mon']) ?></td>
                    <td><?= (int) $ct['so_tin_chi'] ?></td>
                    <td><?= (int) $ct['so_luong'] ?></td>
                    <td><?= (int) $t ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p><strong>Tổng tín chỉ:</strong> <?= (int) $tong ?></p>

        <?php if ($laQuanTri): ?>
        <form method="post" action="don-hang.php" class="form-doi-trang-thai">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int) $keHoach['id'] ?>">
            <label for="trang-thai">Đổi trạng thái:</label>
            <select id="trang-thai" name="trang_thai">
                <?php foreach (KhoKeHoach::TRANG_THAI_HOP_LE as $tt): ?>
                    <option value="<?= e($tt) ?>" <?= $tt === (string) $keHoach['trang_thai'] ? 'selected' : '' ?>>
                        <?= e(KhoKeHoach::nhanTrangThai($tt)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Cập nhật</button>
        </form>
        <?php endif; ?>

        <p><a href="don-hang.php">← Danh sách kế hoạch</a></p>
    </main>
    <?php require __DIR__ . '/inc/footer.php';
    exit;
}

$dsKeHoach = $laQuanTri ? $kho->dsTatCa() : $kho->dsCuaToi($uid);
$tieuDe    = $laQuanTri ? 'Tất cả kế hoạch' : 'Kế hoạch của tôi';
$trang     = 'don-hang';
require __DIR__ . '/inc/header.php';
?>
<main class="noi-dung-chinh">
    <h1><?= e($tieuDe) ?></h1>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <p class="thong-bao thong-bao--thanh-cong" role="status"><?= e($_SESSION['flash_success']) ?></p>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <p class="thong-bao thong-bao--loi" role="alert"><?= e($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($dsKeHoach === []): ?>
        <p>Chưa có kế hoạch nào. <a href="danh-sach.php">Thêm môn</a> vào giỏ rồi <a href="gio-hang.php">gửi kế hoạch</a>.</p>
    <?php else: ?>
        <table class="table-cart">
            <thead>
                <tr>
                    <th>#</th>
                    <?php if ($laQuanTri): ?><th>Người dùng</th><?php endif; ?>
                    <th>Ngày gửi</th><th>Số môn</th><th>Tổng tín chỉ</th><th>Trạng thái</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsKeHoach as $k): ?>
                <tr>
                    <td><?= (int) $k['id'] ?></td>
                    <?php if ($laQuanTri): ?>
                        <td><?= e((string) $k['ho_ten']) ?> (<?= e((string) $k['email']) ?>)</td>
                    <?php endif; ?>
                    <td><?= e((string) $k['ngay_tao']) ?></td>
                    <td><?= (int) $k['so_mon'] ?></td>
                    <td><?= (int) $k['tong_tin_chi'] ?></td>
                    <td><?= e(KhoKeHoach::nhanTrangThai((string) $k['trang_thai'])) ?></td>
                    <td><a href="don-hang.php?id=<?= (int) $k['id'] ?>">Xem chi tiết</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>