<?php
/**
 * Tệp: danh-sach.php
 * Chức năng: Hiển thị danh sách môn học từ MySQL.
 * Hỗ trợ tìm kiếm, lọc theo danh mục, sắp xếp và phân trang.
 */

declare(strict_types=1);

require __DIR__ . '/inc/config.php';

use App\Data\KhoMonHoc;
use App\Services\KeHoachHocTap;

// ==========================================
// 1. CẤU HÌNH TRANG
// ==========================================
$tieuDe = 'Danh sách môn học';
$trang = 'danh-sach';
$goc = '';
$cssTrang = 'css/danh-sach.css';
$scriptsTrang = ['js/tim-kiem.js', 'js/gio-hang.js'];

// ==========================================
// 2. LẤY VÀ KIỂM TRA THAM SỐ GET
// ==========================================
$tuKhoa = layChuoiDauVao($_GET['q'] ?? '');

$danhMucId = filter_var(
    layChuoiDauVao($_GET['dm'] ?? ''),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: null;

$tinChiToiDa = filter_var(
    layChuoiDauVao($_GET['tc_toi_da'] ?? ''),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 20]]
) ?: null;

$danhSachSapXep = ['moi', 'ten', 'tin-chi-tang', 'tin-chi-giam'];
$sapXep = layChuoiDauVao($_GET['sx'] ?? 'moi');
if (!in_array($sapXep, $danhSachSapXep, true)) {
    $sapXep = 'moi';
}

$trangHienTai = filter_var(
    layChuoiDauVao($_GET['trang'] ?? '1'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'default' => 1]]
);

$loc = [
    'q' => $tuKhoa,
    'dm' => $danhMucId,
    'tcToiDa' => $tinChiToiDa,
];

// ==========================================
// 3. LẤY DỮ LIỆU TỪ MYSQL (PHÂN TRANG)
// ==========================================
$kho = new KhoMonHoc(db());
$moiTrang = 6; // Số môn học trên 1 trang

$tongSoMon = $kho->dem($loc);
$soTrang = max(1, (int) ceil($tongSoMon / $moiTrang));
$trangHienTai = min($trangHienTai, $soTrang);

// Lấy danh sách môn của trang hiện tại
$danhSach = $kho->trang($loc, $sapXep, $moiTrang, ($trangHienTai - 1) * $moiTrang);

// ĐÃ SỬA LỖI 7: Đưa truy vấn vào lớp Data (KhoMonHoc)
$dsDanhMuc = $kho->layDanhMuc();

// Lấy kế hoạch học tập (Giữ nguyên logic cũ)
$keHoachHocTap = new KeHoachHocTap();
$idMonDaThem = [];
foreach ($keHoachHocTap->layDanhSachChiTiet()['danh_sach'] as $monDaThem) {
    $idMonDaThem[(int) $monDaThem['id']] = true;
}

// Hàm tạo link phân trang (Giữ nguyên các tham số hiện có)
function lienKetTrang(int $so): string {
    return '?' . http_build_query(array_merge($_GET, ['trang' => $so]));
}

// ==========================================
// 4. NHÚNG HEADER
// ==========================================
require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">

    <h1>Danh sách môn học</h1>

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

    <section class="section-bo-loc">
        <h2>Tra cứu và lọc môn học</h2>
        <p>Dưới đây là danh sách các môn học trong học kỳ 1, năm học 2026–2027.</p>

        <form class="form-tim-kiem" action="danh-sach.php" method="get" data-tim-kiem-api="api/san-pham.php">
            <div class="form-group">
                <label for="tim-kiem">Tìm kiếm môn học</label>
                <input type="search" id="tim-kiem" name="q" class="form-control" 
                       value="<?= e($tuKhoa) ?>" placeholder="Nhập tên hoặc mã môn...">
            </div>

            <div class="form-group">
                <label for="loc-danh-muc">Danh mục</label>
                <select id="loc-danh-muc" name="dm" class="form-control">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($dsDanhMuc as $dm): ?>
                        <!-- ĐÃ SỬA LỖI 6: Bọc e() cho ID danh mục -->
                        <option value="<?= e((string) $dm['id']) ?>" <?= $danhMucId == $dm['id'] ? 'selected' : '' ?>>
                            <?= e($dm['ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tc-toi-da">Tín chỉ tối đa</label>
                <input type="number" id="tc-toi-da" name="tc_toi_da" class="form-control" min="1" max="20"
                       value="<?= e((string) ($tinChiToiDa ?? '')) ?>" placeholder="Ví dụ: 3">
            </div>

            <div class="form-group">
                <label for="sap-xep">Sắp xếp</label>
                <select id="sap-xep" name="sx" class="form-control">
                    <option value="moi" <?= $sapXep === 'moi' ? 'selected' : '' ?>>Mới nhất (ID)</option>
                    <option value="ten" <?= $sapXep === 'ten' ? 'selected' : '' ?>>Tên môn học (A–Z)</option>
                    <option value="tin-chi-giam" <?= $sapXep === 'tin-chi-giam' ? 'selected' : '' ?>>Tín chỉ (Giảm dần)</option>
                    <option value="tin-chi-tang" <?= $sapXep === 'tin-chi-tang' ? 'selected' : '' ?>>Tín chỉ (Tăng dần)</option>
                </select>
            </div>

            <button type="submit" class="btn-loc">Lọc kết quả</button>
        </form>
    </section>

    <section>
        <p id="tim-kiem-status" role="status" aria-live="polite"></p>
        <h2 id="ket-qua-tim-kiem" class="tieu-de-phan">
            Kết quả tìm kiếm (<?= e((string) $tongSoMon) ?> môn)
        </h2>

        <div id="danh-sach-ket-qua" class="luoi-san-pham">
            <?php if (empty($danhSach)): ?>
                <p class="thong-bao-khong-co">Rất tiếc, không tìm thấy môn học nào khớp với tiêu chí của bạn.</p>
            <?php else: ?>
                <?php foreach ($danhSach as $mon): ?>
                    <?php
                    // Kiểm tra an toàn: nếu model không có thuộc tính trangThai thì không bị Warning
                    $classTrangThai = 'trang-thai--mac-dinh';
                    $coTrangThai = isset($mon->trangThai) && !empty($mon->trangThai);
                    
                    if ($coTrangThai) {
                        if ($mon->trangThai === 'Đang học') $classTrangThai = 'trang-thai--dang-hoc';
                        elseif ($mon->trangThai === 'Đã hoàn thành') $classTrangThai = 'trang-thai--hoan-thanh';
                        elseif ($mon->trangThai === 'Đăng ký mới') $classTrangThai = 'trang-thai--dang-ky';
                    }

                    // Không tạo thẻ ảnh khi dữ liệu không có ảnh.
                    $anh = trim((string)($mon->anh ?? ''));
                    $duongDanAnh = $anh === ''
                        ? ''
                        : (str_starts_with($anh, 'http') || str_starts_with($anh, 'images/')
                            ? $anh
                            : 'images/' . $anh);
                    ?>

                    <article class="the-san-pham">
                        <?php if ($duongDanAnh !== ''): ?>
                            <img src="<?= e($duongDanAnh) ?>" alt="<?= e($mon->tenMon) ?>" width="400" height="250" style="object-fit: cover;" loading="lazy">
                        <?php endif; ?>
                        
                        <div class="the-san-pham__than">
                            <h3><?= e($mon->tenMon) ?></h3>
                            <p><strong>Mã môn:</strong> <?= e($mon->maMon) ?></p>
                            <p><strong>Số tín chỉ:</strong> <?= e((string) $mon->soTinChi) ?></p>
                            
                            <?php if ($coTrangThai): ?>
                                <p><strong>Trạng thái:</strong>
                                    <span class="trang-thai <?= e($classTrangThai) ?>"><?= e($mon->trangThai) ?></span>
                                </p>
                            <?php endif; ?>

                            <div class="the-san-pham__hanh-dong">
                                <div class="the-san-pham__o-xanh">
                                    <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=<?= e((string) $mon->id) ?>">
                                        Xem chi tiết &rarr;
                                    </a>

                                    <button type="button" class="nut-yeu-thich" data-yeu-thich-id="<?= e((string) $mon->id) ?>" aria-pressed="false" aria-label="Thêm vào yêu thích">
                                        <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                        <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                                    </button>
                                </div>
                                
                                <form action="gio-hang.php" method="post" class="form-them-ke-hoach" data-gio-hang-api="api/gio-hang.php">
                                    <input type="hidden" name="quay_lai" value="danh-sach.php">
                                    <input type="hidden" name="hanh_dong" value="them">
                                    <input type="hidden" name="id" value="<?= e((string) $mon->id) ?>">
                                    <input type="hidden" name="so_luong" value="1">
                                    <button type="submit" class="nut-them-ke-hoach<?= isset($idMonDaThem[(int) $mon->id]) ? ' nut-them-ke-hoach--da-them' : '' ?>" <?= isset($idMonDaThem[(int) $mon->id]) ? 'disabled aria-disabled="true"' : '' ?>>
                                        <?= isset($idMonDaThem[(int) $mon->id]) ? 'Đã thêm vào kế hoạch' : 'Thêm vào kế hoạch học tập' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- THANH PHÂN TRANG -->
        <div id="phan-trang-api">
        <?php if ($soTrang > 1): ?>
            <nav aria-label="Phân trang" style="margin-top: 30px; text-align: center;">
                <ul class="pagination" style="display: inline-flex; gap: 8px; list-style: none; padding: 0;">
                    <?php for ($i = 1; $i <= $soTrang; $i++): ?>
                        <li>
                            <a href="<?= e(lienKetTrang($i)) ?>"
                               style="padding: 8px 14px; border: 1px solid #ddd; text-decoration: none; border-radius: 4px; 
                                      <?= $i === $trangHienTai ? 'background-color: #0d6efd; color: white; border-color: #0d6efd;' : 'background-color: #fff; color: #333;' ?>">

                                <?= e((string) $i) ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
        </div>
    </section>

</main>

<?php
require __DIR__ . '/inc/footer.php';
?>
