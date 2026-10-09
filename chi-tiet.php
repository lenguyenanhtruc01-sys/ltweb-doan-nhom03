<?php
/**
 * Tệp: chi-tiet.php (Duy — Phần A)
 * Trang chi tiết môn học theo ?id=
 * - filter_var kiểm tra id là số nguyên; id sai / không tồn tại -> 404.php.
 * - Ghi cookie da_xem (tối đa 4 id, mới nhất đầu) TRƯỚC output.
 * Thử: ?id=1 (OK) | ?id=abc | ?id=999999 | không id -> 404.
 * Kiểm tra cookie: DevTools > Application > Cookies > da_xem.
 */
declare(strict_types=1);
require __DIR__ . '/inc/config.php';

use App\Data\KhoMonHoc;

$kho = new KhoMonHoc();

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$mh = ($id !== false && $id > 0) ? $kho->timTheoId($id) : null;

if ($mh === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Cookie "đã xem gần đây": tối đa 4 id, mới nhất đứng đầu.
$cu  = array_filter(
    array_map('intval', explode(',', $_COOKIE['da_xem'] ?? '')),
    static fn(int $x): bool => $x > 0
);
$moi = array_slice(array_unique(array_merge([(int) $mh->id], $cu)), 0, 4);

setcookie('da_xem', implode(',', $moi), [
    'expires'  => time() + 30 * 24 * 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$tieuDe   = (string) $mh->tenMon;
$trang    = 'danh-sach';
$cssTrang = 'css/chi-tiet.css';
require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
  <h1><?= e((string) $mh->tenMon) ?></h1>

  <article class="chi-tiet__noi-dung">
    <p><strong>Mã môn:</strong> <?= e((string) $mh->maMon) ?></p>
    <p><strong>Số tín chỉ:</strong> <?= (int) $mh->soTinChi ?></p>
    <?php if ($mh->diemChu !== null && $mh->diemChu !== ''): ?>
      <p><strong>Điểm chữ:</strong> <?= e((string) $mh->diemChu) ?></p>
    <?php endif; ?>
    <?php if ($mh->diemHe4 !== null): ?>
      <p><strong>Điểm hệ 4:</strong> <?= e((string) $mh->diemHe4) ?></p>
    <?php endif; ?>
    <?php if ($mh->ketQua !== null && $mh->ketQua !== ''): ?>
      <p><strong>Kết quả:</strong> <?= e((string) $mh->ketQua) ?></p>
    <?php endif; ?>
  </article>

  <form action="gio-hang.php" method="post">
    <input type="hidden" name="hanh_dong" value="them">
    <input type="hidden" name="id" value="<?= (int) $mh->id ?>">
    <input type="hidden" name="so_luong" value="1">
    <button type="submit" class="nut nut--chinh">Thêm vào kế hoạch học tập</button>
  </form>

  <p><a href="danh-sach.php">← Về danh sách môn học</a></p>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>
