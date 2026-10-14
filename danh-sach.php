<?php
/**
 * Tệp: danh-sach.php
 * Chức năng: Hiển thị danh sách môn học từ tệp JSON.
 * Hỗ trợ tìm kiếm, lọc trạng thái và sắp xếp bằng GET.
 * Kiểm thử: mở trang, tìm kiếm, lọc, sắp xếp và kiểm tra URL.
 */

declare(strict_types=1);

require __DIR__ . '/inc/config.php';

use App\Data\KhoMonHoc;

// ==========================================
// 1. CẤU HÌNH TRANG
// ==========================================

$tieuDe = 'Danh sách môn học';
$trang = 'danh-sach';
$goc = '';
$cssTrang = 'css/danh-sach.css';

// ==========================================
// 2. LẤY DỮ LIỆU TỪ LỚP TRUY CẬP DỮ LIỆU
// ==========================================

$kho = new KhoMonHoc();
$danhSach = $kho->tatCa();

// ==========================================
// 3. LẤY VÀ KIỂM TRA THAM SỐ GET
// ==========================================

// Tìm kiếm: ?q=...
$tuKhoa = trim((string) ($_GET['q'] ?? ''));

// Lọc trạng thái: ?dm=...
$trangThaiChoPhep = [
    'tat-ca',
    'Đang học',
    'Đã hoàn thành',
    'Đăng ký mới',
];

$trangThai = (string) ($_GET['dm'] ?? 'tat-ca');

if (!in_array($trangThai, $trangThaiChoPhep, true)) {
    $trangThai = 'tat-ca';
}

// Sắp xếp: ?sx=...
$sapXepChoPhep = [
    'mac-dinh',
    'ten-az',
    'tin-chi-giam',
    'tin-chi-tang',
];

$sapXep = (string) ($_GET['sx'] ?? 'mac-dinh');

if (!in_array($sapXep, $sapXepChoPhep, true)) {
    $sapXep = 'mac-dinh';
}

// ==========================================
// 4. TÌM KIẾM VÀ LỌC
// ==========================================

$danhSach = array_filter(
    $danhSach,
    function ($mon) use ($tuKhoa, $trangThai): bool {

        // Lọc theo trạng thái.
        if (
            $trangThai !== 'tat-ca'
            && $mon->trangThai !== $trangThai
        ) {
            return false;
        }

        // Không có từ khóa thì giữ lại môn học.
        if ($tuKhoa === '') {
            return true;
        }

        // Tìm kiếm không phân biệt chữ hoa/chữ thường.
        $matchTen = mb_stripos(
            $mon->tenMon,
            $tuKhoa,
            0,
            'UTF-8'
        ) !== false;

        $matchMa = mb_stripos(
            $mon->maMon,
            $tuKhoa,
            0,
            'UTF-8'
        ) !== false;

        return $matchTen || $matchMa;
    }
);

// ==========================================
// 5. SẮP XẾP
// ==========================================

usort(
    $danhSach,
    function ($a, $b) use ($sapXep): int {

        switch ($sapXep) {

            case 'ten-az':
                return strcasecmp(
                    $a->tenMon,
                    $b->tenMon
                );

            case 'tin-chi-giam':
                return $b->soTinChi <=> $a->soTinChi;

            case 'tin-chi-tang':
                return $a->soTinChi <=> $b->soTinChi;

            case 'mac-dinh':
            default:
                return $a->id <=> $b->id;
        }
    }
);

// ==========================================
// 6. NHÚNG HEADER SAU KHI XỬ LÝ PHP
// ==========================================

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">

    <h1>Danh sách môn học</h1>

    <section class="section-bo-loc">

        <h2>Tra cứu và lọc môn học</h2>

        <p>
            Dưới đây là danh sách các môn học trong học kỳ 1,
            năm học 2026–2027. Sinh viên có thể tra cứu nhanh
            thông tin, lọc theo trạng thái hoặc sắp xếp theo
            nhu cầu học tập.
        </p>

        <form
            class="form-tim-kiem"
            action="danh-sach.php"
            method="get"
        >

            <div class="form-group">

                <label for="tim-kiem">
                    Tìm kiếm môn học
                </label>

                <input
                    type="search"
                    id="tim-kiem"
                    name="q"
                    class="form-control"
                    value="<?= e($tuKhoa) ?>"
                    placeholder="Nhập tên hoặc mã môn..."
                >

            </div>

            <div class="form-group">

                <label for="loc-trang-thai">
                    Trạng thái
                </label>

                <select
                    id="loc-trang-thai"
                    name="dm"
                    class="form-control"
                >

                    <option
                        value="tat-ca"
                        <?= $trangThai === 'tat-ca' ? 'selected' : '' ?>
                    >
                        Tất cả
                    </option>

                    <option
                        value="Đang học"
                        <?= $trangThai === 'Đang học' ? 'selected' : '' ?>
                    >
                        Đang học
                    </option>

                    <option
                        value="Đã hoàn thành"
                        <?= $trangThai === 'Đã hoàn thành' ? 'selected' : '' ?>
                    >
                        Đã hoàn thành
                    </option>

                    <option
                        value="Đăng ký mới"
                        <?= $trangThai === 'Đăng ký mới' ? 'selected' : '' ?>
                    >
                        Đăng ký mới
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="sap-xep">
                    Sắp xếp
                </label>

                <select
                    id="sap-xep"
                    name="sx"
                    class="form-control"
                >

                    <option
                        value="mac-dinh"
                        <?= $sapXep === 'mac-dinh' ? 'selected' : '' ?>
                    >
                        Mặc định (ID)
                    </option>

                    <option
                        value="ten-az"
                        <?= $sapXep === 'ten-az' ? 'selected' : '' ?>
                    >
                        Tên môn học (A–Z)
                    </option>

                    <option
                        value="tin-chi-giam"
                        <?= $sapXep === 'tin-chi-giam' ? 'selected' : '' ?>
                    >
                        Tín chỉ (Giảm dần)
                    </option>

                    <option
                        value="tin-chi-tang"
                        <?= $sapXep === 'tin-chi-tang' ? 'selected' : '' ?>
                    >
                        Tín chỉ (Tăng dần)
                    </option>

                </select>

            </div>

            <button
                type="submit"
                class="btn-loc"
            >
                Lọc kết quả
            </button>

        </form>

    </section>

    <section>

        <h2 class="tieu-de-phan">
            Tiến độ các môn học
            (<?= count($danhSach) ?> kết quả)
        </h2>

        <div class="luoi-san-pham">

            <?php if (empty($danhSach)): ?>

                <p class="thong-bao-khong-co">
                    Rất tiếc, không tìm thấy môn học nào
                    khớp với tiêu chí tìm kiếm của bạn.
                </p>

            <?php else: ?>

                <?php foreach ($danhSach as $mon): ?>

                    <?php
                    $classTrangThai = 'trang-thai--mac-dinh';

                    if ($mon->trangThai === 'Đang học') {
                        $classTrangThai = 'trang-thai--dang-hoc';
                    } elseif ($mon->trangThai === 'Đã hoàn thành') {
                        $classTrangThai = 'trang-thai--hoan-thanh';
                    } elseif ($mon->trangThai === 'Đăng ký mới') {
                        $classTrangThai = 'trang-thai--dang-ky';
                    }
                    ?>

                    <article class="the-san-pham">

                        <img
                            src="<?= e($mon->anh) ?>"
                            alt="<?= e($mon->tenMon) ?>"
                            width="400"
                            height="250"
                            loading="lazy"
                        >

                        <div class="the-san-pham__than">

                            <h3>
                                <?= e($mon->tenMon) ?>
                            </h3>

                            <p>
                                <strong>Mã môn:</strong>
                                <?= e($mon->maMon) ?>
                            </p>

                            <p>
                                <strong>Số tín chỉ:</strong>
                                <?= e((string) $mon->soTinChi) ?>
                            </p>

                            <p>
                                <strong>Trạng thái:</strong>

                                <span
                                    class="trang-thai <?= e($classTrangThai) ?>"
                                >
                                    <?= e($mon->trangThai) ?>
                                </span>

                            </p>

                            <div class="the-san-pham__hanh-dong">

                                <div class="the-san-pham__o-xanh">

                                    <a
                                        class="the-san-pham__lien-ket"
                                        href="chi-tiet.php?id=<?= e((string) $mon->id) ?>"
                                    >
                                        Xem chi tiết &rarr;
                                    </a>

                                    <button
                                        type="button"
                                        class="nut-yeu-thich"
                                        data-yeu-thich-id="<?= e((string) $mon->id) ?>"
                                        aria-pressed="false"
                                        aria-label="Thêm vào yêu thích"
                                    >

                                        <span
                                            class="nut-yeu-thich__icon"
                                            aria-hidden="true"
                                        >
                                            ♡
                                        </span>

                                        <span
                                            class="visually-hidden"
                                            data-nhan-yeu-thich
                                        >
                                            Thêm vào yêu thích
                                        </span>

                                    </button>

                                </div>

                                <form
                                    action="gio-hang.php"
                                    method="post"
                                    class="form-them-ke-hoach"
                                >

                                    <input
                                        type="hidden"
                                        name="hanh_dong"
                                        value="them"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= e((string) $mon->id) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="so_luong"
                                        value="1"
                                    >

                                    <button
                                        type="submit"
                                        class="nut-them-ke-hoach"
                                    >
                                        Thêm vào kế hoạch học tập
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php
require __DIR__ . '/inc/footer.php';
?>
