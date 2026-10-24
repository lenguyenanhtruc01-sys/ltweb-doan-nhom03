<?php
/**
 * Tệp: chi-tiet.php
 * Hiển thị thông tin chi tiết môn học theo tham số ?id=.
 * ID không hợp lệ hoặc không tồn tại sẽ trả về trang 404.
 * Lưu tối đa 4 môn đã xem gần đây bằng cookie da_xem.
 */
declare(strict_types=1);

use App\Data\KhoMonHoc;
use App\Services\KeHoachHocTap;

require __DIR__ . '/inc/config.php';

$kho = new KhoMonHoc(db());

// Chỉ nhận ID dạng số nguyên đơn; chặn trường hợp ?id[]=1.
$giaTriId = $_GET['id'] ?? null;

$id = (is_string($giaTriId) || is_int($giaTriId))
    ? filter_var($giaTriId, FILTER_VALIDATE_INT)
    : false;

$mh = ($id !== false && $id > 0)
    ? $kho->timTheoId($id)
    : null;

// ID sai hoặc môn học không tồn tại: trả về trang 404.
if ($mh === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Đọc cookie như dữ liệu không đáng tin cậy.
$idsCu = [];
$cookieRaw = $_COOKIE['da_xem'] ?? '';

if (is_string($cookieRaw) && $cookieRaw !== '') {
    // Cookie bình thường chỉ có tối đa 4 ID.
    // Giới hạn số mục đọc nếu cookie bị chỉnh sửa.
    $cacIdCu = array_slice(explode(',', $cookieRaw), 0, 20);

    foreach ($cacIdCu as $giaTriCu) {
        $idCu = filter_var(
            trim($giaTriCu),
            FILTER_VALIDATE_INT
        );

        // Bỏ giá trị không phải số nguyên dương.
        if ($idCu === false || $idCu <= 0) {
            continue;
        }

        // Không thêm lại môn hiện tại hoặc ID đã có.
        if (
            $idCu === (int) $mh->id
            || in_array($idCu, $idsCu, true)
        ) {
            continue;
        }

        // Chỉ giữ ID của môn thực sự tồn tại.
        if ($kho->timTheoId($idCu) === null) {
            continue;
        }

        $idsCu[] = $idCu;

        // Môn hiện tại đứng đầu nên chỉ giữ thêm 3 môn cũ.
        if (count($idsCu) >= 3) {
            break;
        }
    }
}

$dsDaXemMoi = array_merge(
    [(int) $mh->id],
    $idsCu
);

// Ghi cookie trước khi xuất HTML.
setcookie('da_xem', implode(',', $dsDaXemMoi), [
    'expires'  => time() + 30 * 24 * 60 * 60,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Các biến dùng chung với header.php.
$tieuDe   = (string) $mh->tenMon;
$trang    = 'danh-sach';
$lopBody  = 'trang-chi-tiet';
$cssTrang = 'css/chi-tiet.css';

$keHoachHocTap = new KeHoachHocTap();
$idMonDaThem = array_column(
    $keHoachHocTap->layDanhSachChiTiet()['danh_sach'],
    'id'
);
$monDaCoTrongKeHoach = in_array((int) $mh->id, $idMonDaThem, true);

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <h1 id="tieu-de-chi-tiet">
        <?= e((string) $mh->tenMon) ?>
    </h1>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="thong-bao-ke-hoach thong-bao-ke-hoach--thanh-cong" role="status">
            <?= e($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="thong-bao-ke-hoach thong-bao-ke-hoach--loi" role="alert">
            <?= e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <article
        id="chi-tiet"
        class="chi-tiet__noi-dung"
        aria-labelledby="tieu-de-chi-tiet">

        <?php if (trim((string) $mh->anh) !== ''): ?>
            <figure>
                <img
                    src="<?= e((string) $mh->anh) ?>"
                    alt="<?= e(
                        'Hình minh họa môn học '
                        . (string) $mh->tenMon
                    ) ?>"
                    width="600"
                    height="400">

                <figcaption>
                    <?= e((string) $mh->tenMon) ?>
                </figcaption>
            </figure>
        <?php endif; ?>

        <ul class="thong-tin-mon">
            <li>
                <strong>Mã môn:</strong>
                <?= e((string) $mh->maMon) ?>
            </li>

            <li>
                <strong>Số tín chỉ:</strong>
                <?= (int) $mh->soTinChi ?>
            </li>

            <?php if (trim((string) $mh->trangThai) !== ''): ?>
                <li>
                    <strong>Trạng thái:</strong>
                    <?= e((string) $mh->trangThai) ?>
                </li>
            <?php endif; ?>

            <?php if (
                $mh->diemChu !== null
                && $mh->diemChu !== ''
            ): ?>
                <li>
                    <strong>Điểm chữ:</strong>
                    <?= e((string) $mh->diemChu) ?>
                </li>
            <?php endif; ?>

            <?php if ($mh->diemHe4 !== null): ?>
                <li>
                    <strong>Điểm hệ 4:</strong>
                    <?= e((string) $mh->diemHe4) ?>
                </li>
            <?php endif; ?>

            <?php if (
                $mh->ketQua !== null
                && $mh->ketQua !== ''
            ): ?>
                <li>
                    <strong>Kết quả:</strong>
                    <?= e((string) $mh->ketQua) ?>
                </li>
            <?php endif; ?>
        </ul>

        <div class="form-them-gio-hang">
            <form action="gio-hang.php" method="post">
                <input
                    type="hidden"
                    name="quay_lai"
                    value="chi-tiet.php?id=<?= (int) $mh->id ?>">

                <input
                    type="hidden"
                    name="hanh_dong"
                    value="them">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $mh->id ?>">

                <input
                    type="hidden"
                    name="so_luong"
                    value="1">

                <button
                    type="submit"
                    class="nut nut--chinh nut-them-ke-hoach-ct<?= $monDaCoTrongKeHoach ? ' nut-them-ke-hoach-ct--da-them' : '' ?>"
                    <?= $monDaCoTrongKeHoach ? 'disabled aria-disabled="true"' : '' ?>>
                    <?= $monDaCoTrongKeHoach
                        ? 'Đã thêm vào kế hoạch học tập'
                        : 'Thêm vào kế hoạch học tập' ?>
                </button>
            </form>
        </div>

        <p>
            <a
                class="nut-quay-lai"
                href="danh-sach.php">
                ← Về danh sách môn học
            </a>
        </p>
    </article>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>
