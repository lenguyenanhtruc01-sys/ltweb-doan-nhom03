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

$id = null;
if (is_string($giaTriId) || is_int($giaTriId)) {
    $id = filter_var(
        $giaTriId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
}

$mh = $id !== false && $id !== null
    ? $kho->timTheoId((int) $id)
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
    $cacIdCu = array_slice(explode(',', $cookieRaw), 0, 20);
    $idHopLe = [];

    foreach ($cacIdCu as $giaTriCu) {
        $idCu = filter_var(trim((string) $giaTriCu), FILTER_VALIDATE_INT);
        if ($idCu === false || $idCu <= 0) {
            continue;
        }
        if (
            $idCu === (int) $mh->id
            || in_array($idCu, $idHopLe, true)
        ) {
            continue;
        }
        $idHopLe[] = (int) $idCu;
    }

    $idHopLe = array_values(array_unique($idHopLe, SORT_NUMERIC));
    $monCuTheoId = $kho->theoNhieuId($idHopLe);
    foreach ($idHopLe as $idCu) {
        if (!isset($monCuTheoId[$idCu])) {
            continue;
        }
        $idsCu[] = $idCu;
        if (count($idsCu) >= 3) {
            break;
        }
    }
}
$monCungDanhMuc = $kho->cungDanhMuc(
    (int) $mh->danhMucId,
    (int) $mh->id
);

$dsDaXemMoi = array_values(array_unique(array_merge(
    [(int) $mh->id],
    $idsCu
), SORT_NUMERIC));
$dsDaXemMoi = array_slice($dsDaXemMoi, 0, 4);

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
$scriptsTrang = ['js/gio-hang.js'];

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
                <strong>Danh mục:</strong>
                <?= e((string) $mh->tenDanhMuc) ?>
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
            <form action="gio-hang.php" method="post" data-gio-hang-api="api/gio-hang.php">
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

    <?php if ($monCungDanhMuc !== []): ?>
        <section class="khu-vuc" aria-labelledby="tieu-de-cung-danh-muc">
            <h2 id="tieu-de-cung-danh-muc">Môn học cùng danh mục</h2>
            <div class="luoi-the">
                <?php foreach ($monCungDanhMuc as $mon): ?>
                    <article class="the">
                        <h3 class="the__tieu-de">
                            <a class="the__lien-ket" href="chi-tiet.php?id=<?= (int) $mon->id ?>">
                                <?= e((string) $mon->tenMon) ?>
                            </a>
                        </h3>
                        <p class="the__noi-dung">
                            Mã môn: <strong><?= e((string) $mon->maMon) ?></strong>
                            — <?= (int) $mon->soTinChi ?> tín chỉ
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>
