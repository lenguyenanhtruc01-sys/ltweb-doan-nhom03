<?php
declare(strict_types=1);

/**
 * Quản trị môn học: phân trang, thêm, sửa, ảnh và xác nhận xóa bằng form POST.
 * Chung bộ kiểm tra dữ liệu, PDO prepared statements, quyền quản trị và CSRF.
 * Thử mã trùng 1062, xóa môn đã có kế hoạch 1451, dữ liệu mảng và ảnh giả.
 * POST thành công chuyển hướng 303; form lỗi giữ dữ liệu để sửa.
 */
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoQuanTriMonHoc;
use App\Services\KiemTraMonHoc;
use App\Services\TaiAnh;

yeuCauQuyen('quan_tri');
$kho = new KhoQuanTriMonHoc(db());
$danhMuc = $kho->danhMuc();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST'); http_response_code(405); exit('Phương thức không hợp lệ.');
}
$docId = static fn (mixed $v): int|false => is_string($v)
    ? filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : false;
$idRaw = $method === 'POST' ? ($_POST['id'] ?? '') : ($_GET['sua'] ?? '');
$id = $idRaw === '' ? null : $docId($idRaw);
$loi = [];
$duLieu = ['ma_mon'=>'', 'ten_mon'=>'', 'danh_muc_id'=>'', 'so_tin_chi'=>'3', 'so_cho'=>'50', 'mo_ta'=>'', 'anh'=>null];
$monCu = $id !== null && $id !== false ? $kho->lay($id) : null;
if ($id === false || ($id !== null && $monCu === null)) {
    http_response_code(404); require __DIR__ . '/404.php'; exit;
}
if ($monCu !== null) $duLieu = $monCu;
if ($method === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!is_string($token) || !csrfKiemTra($token)) {
        $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
        header('Location: quan-tri-mon-hoc.php', true, 303); exit;
    }
    [$duLieu, $loi] = KiemTraMonHoc::kiemTra($_POST, $danhMuc);
    $duLieu['anh'] = $monCu['anh'] ?? null;
    $tenMoi = null;
    if ($loi === []) {
        try {
            $tep = $_FILES['anh'] ?? null;
            if ($tep !== null && !is_array($tep)) throw new RuntimeException('Ảnh không hợp lệ.');
            if ($tep !== null) $tenMoi = (new TaiAnh(__DIR__ . '/uploads'))->luu($tep);
            if ($tenMoi !== null) $duLieu['anh'] = 'uploads/' . $tenMoi;
            if ($id === null) {
                $id = $kho->them($duLieu);
            } elseif (!$kho->sua($id, $duLieu)) {
                throw new RuntimeException('Môn học không còn tồn tại.');
            }
            $_SESSION['flash_success'] = 'Đã lưu môn học #' . $id . '.';
            header('Location: quan-tri-mon-hoc.php?sua=' . $id, true, 303); exit;
        } catch (Throwable $err) {
            if ($tenMoi !== null) @unlink(__DIR__ . '/uploads/' . $tenMoi);
            $duLieu['anh'] = $monCu['anh'] ?? null;
            if ($err instanceof PDOException) {
                if ((int) ($err->errorInfo[1] ?? 0) === 1062) $loi[] = 'Mã môn đã tồn tại. Vui lòng chọn mã khác.';
                else throw $err;
            } elseif ($err instanceof RuntimeException) $loi[] = $err->getMessage();
            else throw $err;
        }
    }
    http_response_code(422);
}
$xoaRaw = $_GET['xoa'] ?? '';
$xoaId = $docId($xoaRaw);
$monXoa = $xoaId !== false ? $kho->lay($xoaId) : null;
$tongTrang = max(1, (int) ceil($kho->dem()/10));
$trangSo = min($tongTrang, max(1, $docId($_GET['trang'] ?? '1') ?: 1));
$cacMon = $kho->danhSach(10, ($trangSo-1)*10);
$thanhCong = $_SESSION['flash_success'] ?? '';
$thatBai = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$tieuDe = 'Quản trị môn học'; $trang = 'quan-tri'; $goc = ''; $cssTrang = 'css/quan-tri.css';
require __DIR__ . '/inc/header.php';
?>
<main class="bao quan-tri-mon">
    <h1>Quản trị môn học</h1>
    <p><a href="quan-tri.php">Xem liên hệ đã nhận</a> · <a href="don-hang.php">Quản lý kế hoạch</a></p>
    <?php if ($thanhCong !== ''): ?><p class="quan-tri-mon__thanh-cong" role="status"><?= e($thanhCong) ?></p><?php endif; ?>
    <?php if ($thatBai !== ''): ?><p class="quan-tri-mon__loi" role="alert"><?= e($thatBai) ?></p><?php endif; ?>
    <?php if ($monXoa !== null): ?>
    <section class="quan-tri-mon__xac-nhan" aria-labelledby="xac-nhan-xoa">
        <h2 id="xac-nhan-xoa">Xác nhận xóa môn học</h2>
        <p>Bạn muốn xóa <?= e($monXoa['ma_mon']) ?> — <?= e($monXoa['ten_mon']) ?>?</p>
        <form method="post" action="xoa-mon-hoc.php">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= e($monXoa['id']) ?>">
            <button class="nut" type="submit">Xác nhận xóa</button>
            <a href="quan-tri-mon-hoc.php">Hủy</a>
        </form>
    </section>
    <?php endif; ?>
    <section aria-labelledby="tieu-de-form-mon">
        <h2 id="tieu-de-form-mon"><?= e($id === null ? 'Thêm môn học' : 'Sửa môn học') ?></h2>
        <?php if ($loi !== []): ?><ul class="quan-tri-mon__loi" role="alert"><?php foreach ($loi as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <form action="quan-tri-mon-hoc.php" method="post" enctype="multipart/form-data" class="quan-tri-mon__form">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
            <label for="ma-mon">Mã môn</label><input id="ma-mon" name="ma_mon" maxlength="20" required value="<?= e($duLieu['ma_mon']) ?>">
            <label for="ten-mon">Tên môn</label><input id="ten-mon" name="ten_mon" maxlength="200" required value="<?= e($duLieu['ten_mon']) ?>">
            <label for="danh-muc">Danh mục</label><select id="danh-muc" name="danh_muc_id" required>
                <option value="">Chọn danh mục</option>
                <?php foreach ($danhMuc as $dm): ?><option value="<?= e($dm['id']) ?>" <?= (string) $duLieu['danh_muc_id'] === (string) $dm['id'] ? 'selected' : '' ?>><?= e($dm['ten']) ?></option><?php endforeach; ?>
            </select>
            <label for="tin-chi">Số tín chỉ</label><input id="tin-chi" name="so_tin_chi" type="number" min="1" max="30" required value="<?= e($duLieu['so_tin_chi']) ?>">
            <label for="so-cho">Số chỗ còn lại</label><input id="so-cho" name="so_cho" type="number" min="0" max="1000000" required value="<?= e($duLieu['so_cho']) ?>">
            <label for="mo-ta">Mô tả</label><textarea id="mo-ta" name="mo_ta" maxlength="10000" rows="4"><?= e($duLieu['mo_ta'] ?? '') ?></textarea>
            <label for="anh-mon">Ảnh môn học (JPG, PNG, WebP, tối đa 2MB)</label><input id="anh-mon" type="file" name="anh" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($duLieu['anh'])): ?><img src="<?= e($duLieu['anh']) ?>" alt="Ảnh hiện tại của môn học" width="120" height="80"><?php endif; ?>
            <div><button class="nut nut--chinh" type="submit">Lưu môn học</button> <a href="quan-tri-mon-hoc.php">Tạo môn mới</a></div>
        </form>
    </section>
    <section aria-labelledby="danh-sach-quan-tri">
        <h2 id="danh-sach-quan-tri">Danh sách môn học</h2>
        <div class="quan-tri-mon__bang" role="region" aria-label="Bảng môn học" tabindex="0">
            <table><caption>Môn học — trang <?= e($trangSo) ?>/<?= e($tongTrang) ?></caption>
                <thead><tr><th scope="col">Mã</th><th scope="col">Tên môn</th><th scope="col">Danh mục</th><th scope="col">Tín chỉ</th><th scope="col">Chỗ còn</th><th scope="col">Thao tác</th></tr></thead>
                <tbody><?php foreach ($cacMon as $mon): ?><tr>
                    <td><?= e($mon['ma_mon']) ?></td><td><?= e($mon['ten_mon']) ?></td><td><?= e($mon['ten_danh_muc']) ?></td>
                    <td><?= e($mon['so_tin_chi']) ?></td><td><?= e($mon['so_cho']) ?></td>
                    <td><a href="quan-tri-mon-hoc.php?sua=<?= e($mon['id']) ?>">Sửa</a> · <a href="quan-tri-mon-hoc.php?xoa=<?= e($mon['id']) ?>#xac-nhan-xoa">Xóa</a></td>
                </tr><?php endforeach; ?></tbody>
            </table>
        </div>
        <nav aria-label="Phân trang môn học"><p>
            <?php for ($i=1; $i<=$tongTrang; $i++): ?><a href="quan-tri-mon-hoc.php?trang=<?= e($i) ?>" <?= $i === $trangSo ? 'aria-current="page"' : '' ?>><?= e($i) ?></a> <?php endfor; ?>
        </p></nav>
    </section>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>
