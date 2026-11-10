<?php
/**
 * Trang Duy: chọn món, lịch sử oẳn tù tì và thống kê thời tiết bằng MySQL.
 * Khách chọn món riêng trong session; chỉ chủ trang lưu thêm vào danh sách chung.
 * Ba form POST kiểm tra CSRF, thành công chuyển hướng để tránh gửi lại khi F5.
 * Thử token đúng/sai/mảng, khách/chủ trang và tắt JavaScript trên CSDL thử.
 */
declare(strict_types=1);
require __DIR__ . '/../../inc/config.php';

use App\Data\KhoCaNhan;

const MSSV_DUY = '3120224034';

$goc      = '../../';
$tieuDe   = 'Giới thiệu — Dương Bảo Duy';
$trang    = '';
$cssTrang = 'css/canhan.css';
$scriptsTrang = ['thanhvien/3120224034_Duy/canhan.js'];
$lopBody  = 'trang-ca-nhan';

$khoCaNhan = new KhoCaNhan(db());
$laAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

/** Trả JSON cho fetch (giữ nguyên hợp đồng dữ liệu) rồi kết thúc request. */
function traJsonCaNhan(array $duLieu, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($duLieu, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

/** Chỉ tài khoản liên kết thanh_vien.nguoi_dung_id của Duy mới được sửa dữ liệu dùng chung. */
function laChuTrangDuy(KhoCaNhan $kho): bool
{
    $nd = nguoiDung();
    return $nd !== null && $kho->laChuTrang(MSSV_DUY, (int) $nd['id']);
}

// Nhãn dùng chung cho cả trang và phản hồi JSON.
$buaChoPhep  = ['bat_ky' => 'Bất kỳ', 'sang' => 'Sáng', 'trua' => 'Trưa', 'toi' => 'Tối', 'dem' => 'Đêm'];
$labelChon   = ['keo' => '✊ Kéo', 'bua' => '✋ Búa', 'bao' => '✌️ Bao'];
$labelKetQua = ['thang' => '🎉 Thắng', 'thua' => '😢 Thua', 'hoa' => '🤝 Hoà'];
$labelThoiTiet = ['nang' => '☀️ Nắng', 'mua' => '🌧️ Mưa', 'may' => '☁️ Nhiều mây', 'giong_bao' => '⛈️ Giông bão'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // config.php đã chặn POST thiếu mã; kiểm tra lại để trang tự đứng vững.
    // csrfHopLe() nhận mã trong _csrf của form hoặc header X-CSRF-Token của fetch.
    if (!csrfHopLe()) {
        if ($laAjax) {
            traJsonCaNhan(['ok' => false, 'loi' => ['chung' => 'Phiên biểu mẫu không hợp lệ. Hãy tải lại trang.']], 403);
        }
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang rồi thử lại.');
    }
    if ($laAjax) {
        header('X-Content-Type-Options: nosniff');
    }
    $hanhDong = layChuoiDauVao($_POST['hanh_dong'] ?? '');
    $laChuTrang = laChuTrangDuy($khoCaNhan);

    // 1. Chọn món
    if ($hanhDong === 'chon_mon') {
        $loi = [];
        try {
            $dsMonText = $_POST['dsMon'] ?? '';
            $bua = layChuoiDauVao($_POST['bua'] ?? 'bat_ky');
            if (!is_string($dsMonText)) {
                throw new DomainException('Danh sách món hoặc bữa ăn không hợp lệ.');
            }
            if (mb_strlen($dsMonText) > 3000) {
                throw new DomainException('Danh sách món tối đa 3000 ký tự.');
            }
            $mangMon = KhoCaNhan::kiemTraDanhSachMon(explode("\n", $dsMonText), $bua);
            if ($laChuTrang) {
                $khoCaNhan->luuDanhSachMon(MSSV_DUY, $mangMon, $bua);
            }
            $monChon = $khoCaNhan->chonMonNgauNhien($mangMon, $bua);
            $_SESSION['duy_danh_sach_mon'] = $mangMon;
            $_SESSION['duy_mon_an_ket_qua'] = ['mon' => $monChon, 'bua' => $bua, 'thoiGian' => date('H:i:s d/m/Y')];
            $_SESSION['flash_duy'] = ($laChuTrang ? 'Đã lưu danh sách. ' : 'Danh sách chỉ giữ trong phiên này. ')
                . 'Máy đã chọn: ' . $monChon;
        } catch (DomainException $err) {
            $loi['dsMon'] = $err->getMessage();
        } catch (PDOException $err) {
            error_log((string) $err);
            $loi['chung'] = 'Chưa lưu được danh sách món. Vui lòng thử lại sau.';
        }

        if ($laAjax) {
            if ($loi !== []) {
                traJsonCaNhan(['ok' => false, 'loi' => $loi], 422);
            }
            $mon = $_SESSION['duy_mon_an_ket_qua'];
            $daLuu = [];
            foreach ($khoCaNhan->layMonTheoMssv(MSSV_DUY) as $monDaLuu) {
                $daLuu[] = [
                    'id' => (int) $monDaLuu['id'],
                    'ten' => (string) $monDaLuu['ten_mon'],
                    'buaLabel' => $buaChoPhep[$monDaLuu['bua']] ?? $monDaLuu['bua'],
                ];
            }
            traJsonCaNhan([
                'ok' => true,
                'mon' => $mon['mon'],
                'buaLabel' => $buaChoPhep[$mon['bua']] ?? $mon['bua'],
                'thoiGian' => $mon['thoiGian'],
                'chuTrang' => $laChuTrang,
                'daLuu' => $daLuu,
                'thongBao' => (string) ($_SESSION['flash_duy'] ?? ''),
            ]);
        }
        header('Location: gioithieu.php#chon-mon', true, 303);
        exit;
    }

    // 2. Oẳn tù tì
    if ($hanhDong === 'choi_oan_tu_ti') {
        $nguoiChon = layChuoiDauVao($_POST['chon'] ?? '');
        $hopLe = ['keo', 'bua', 'bao'];
        $loi = [];
        if (in_array($nguoiChon, $hopLe, true)) {
            $mayChon   = $hopLe[array_rand($hopLe)];
            $luatThang = ['keo' => 'bao', 'bua' => 'keo', 'bao' => 'bua'];
            $ketQua    = ($nguoiChon === $mayChon) ? 'hoa' : (($luatThang[$nguoiChon] === $mayChon) ? 'thang' : 'thua');
            $khoCaNhan->ghiLichSuOtt(MSSV_DUY, $nguoiChon, $mayChon, $ketQua);
            $_SESSION['flash_duy'] = "Kết quả: Bạn chọn $nguoiChon, Máy chọn $mayChon => Bạn $ketQua!";
        } else {
            $loi['chon'] = 'Vui lòng chọn Kéo, Búa hoặc Bao.';
        }

        if ($laAjax) {
            if ($loi !== []) {
                traJsonCaNhan(['ok' => false, 'loi' => $loi], 422);
            }
            $stOtt = $khoCaNhan->thongKeOtt(MSSV_DUY);
            $tong = array_sum($stOtt);
            $lichSu = [];
            foreach ($khoCaNhan->layLichSuOtt(MSSV_DUY) as $van) {
                $lichSu[] = [
                    'thoiGian' => (string) $van['thoi_gian'],
                    'nguoiLabel' => $labelChon[$van['nguoi_chon']] ?? $van['nguoi_chon'],
                    'mayLabel' => $labelChon[$van['may_chon']] ?? $van['may_chon'],
                    'ketQuaLabel' => $labelKetQua[$van['ket_qua']] ?? $van['ket_qua'],
                ];
            }
            traJsonCaNhan([
                'ok' => true,
                'vua' => [
                    'nguoiLabel' => $labelChon[$nguoiChon],
                    'mayLabel' => $labelChon[$mayChon],
                    'ketQua' => $ketQua,
                    'ketQuaLabel' => $labelKetQua[$ketQua],
                ],
                'thongKe' => [
                    'thang' => (int) $stOtt['thang'],
                    'thua' => (int) $stOtt['thua'],
                    'hoa' => (int) $stOtt['hoa'],
                    'tong' => (int) $tong,
                    'tiLe' => $tong > 0 ? round($stOtt['thang'] * 100 / $tong, 1) : 0,
                ],
                'lichSu' => $lichSu,
                'chuTrang' => $laChuTrang,
            ]);
        }
        header('Location: gioithieu.php#oan-tu-ti', true, 303);
        exit;
    }

    // 3. Check-in thời tiết
    if ($hanhDong === 'check_in_thoi_tiet') {
        $trangThai = layChuoiDauVao($_POST['trang_thai'] ?? '');
        $hopLe = ['nang', 'mua', 'may', 'giong_bao'];
        $loi = [];
        if (in_array($trangThai, $hopLe, true)) {
            $khoCaNhan->themCheckIn(MSSV_DUY, $trangThai);
            $_SESSION['flash_duy'] = "Đã ghi nhận thời tiết! Cảm ơn bạn.";
        } else {
            $loi['trang_thai'] = 'Trạng thái thời tiết không hợp lệ.';
        }

        if ($laAjax) {
            if ($loi !== []) {
                traJsonCaNhan(['ok' => false, 'loi' => $loi], 422);
            }
            $dsThoiTiet = $khoCaNhan->thongKeThoiTiet(MSSV_DUY);
            $tongCheckIn = array_sum(array_column($dsThoiTiet, 'so_luot'));
            $thongKe = [];
            foreach ($dsThoiTiet as $tt) {
                $thongKe[] = [
                    'trangThai' => $tt['trang_thai'],
                    'label' => $labelThoiTiet[$tt['trang_thai']] ?? $tt['trang_thai'],
                    'soLuot' => (int) $tt['so_luot'],
                    'phanTram' => $tongCheckIn > 0 ? round($tt['so_luot'] / $tongCheckIn * 100, 1) : 0,
                ];
            }
            traJsonCaNhan(['ok' => true, 'tong' => (int) $tongCheckIn, 'thongKe' => $thongKe]);
        }
        header('Location: gioithieu.php#thoi-tiet', true, 303);
        exit;
    }

    // 4. Xóa một món đã lưu (chỉ chủ trang; điều kiện AND mssv = ?)
    if ($hanhDong === 'xoa_mon') {
        if (!$laChuTrang) {
            if ($laAjax) {
                traJsonCaNhan(['ok' => false, 'loi' => ['chung' => 'Chỉ chủ trang đã đăng nhập mới được xóa món dùng chung.']], 403);
            }
            http_response_code(403);
            exit('Chỉ chủ trang đã đăng nhập mới được xóa món dùng chung.');
        }
        $id = soNguyenDauVao($_POST['id'] ?? null);
        if ($id === false) {
            if ($laAjax) {
                traJsonCaNhan(['ok' => false, 'loi' => ['id' => 'Mã món không hợp lệ.']], 422);
            }
            http_response_code(422);
            exit('Mã món không hợp lệ.');
        }
        if (!$khoCaNhan->xoaMon($id, MSSV_DUY)) {
            if ($laAjax) {
                traJsonCaNhan(['ok' => false, 'loi' => ['chung' => 'Không tìm thấy món thuộc trang của bạn.']], 404);
            }
            http_response_code(404);
            exit('Không tìm thấy món thuộc trang của bạn.');
        }
        if ($laAjax) {
            $daLuu = [];
            foreach ($khoCaNhan->layMonTheoMssv(MSSV_DUY) as $monDaLuu) {
                $daLuu[] = [
                    'id' => (int) $monDaLuu['id'],
                    'ten' => (string) $monDaLuu['ten_mon'],
                    'buaLabel' => $buaChoPhep[$monDaLuu['bua']] ?? $monDaLuu['bua'],
                ];
            }
            traJsonCaNhan(['ok' => true, 'daLuu' => $daLuu, 'thongBao' => 'Đã xóa món khỏi danh sách dùng chung.']);
        }
        $_SESSION['flash_duy'] = 'Đã xóa món khỏi danh sách dùng chung.';
        header('Location: gioithieu.php#chon-mon', true, 303);
        exit;
    }

    // 5. Xóa lịch sử oẳn tù tì (chỉ chủ trang; điều kiện AND mssv = ?)
    if ($hanhDong === 'reset_oan_tu_ti') {
        if (!$laChuTrang) {
            if ($laAjax) {
                traJsonCaNhan(['ok' => false, 'loi' => ['chung' => 'Chỉ chủ trang đã đăng nhập mới được xóa lịch sử.']], 403);
            }
            http_response_code(403);
            exit('Chỉ chủ trang đã đăng nhập mới được xóa lịch sử.');
        }
        $soVan = $khoCaNhan->xoaLichSuOtt(MSSV_DUY);
        if ($laAjax) {
            traJsonCaNhan(['ok' => true, 'soVan' => $soVan, 'thongBao' => 'Đã xóa ' . $soVan . ' ván của trang.']);
        }
        $_SESSION['flash_duy'] = 'Đã xóa ' . $soVan . ' ván của trang.';
        header('Location: gioithieu.php#oan-tu-ti', true, 303);
        exit;
    }

    if ($laAjax) {
        traJsonCaNhan(['ok' => false, 'loi' => ['chung' => 'Thao tác không hợp lệ.']], 422);
    }
    $_SESSION['flash_duy'] = 'Thao tác không hợp lệ.';
    header('Location: gioithieu.php', true, 303);
    exit;
}

// ============================================================
// LẤY DỮ LIỆU
// ============================================================
$tb = $_SESSION['flash_duy'] ?? '';
unset($_SESSION['flash_duy']);

$dsMonNhap = $_SESSION['duy_danh_sach_mon'] ?? $khoCaNhan->layDanhSachMon(MSSV_DUY);
$ketQuaMon = $_SESSION['duy_mon_an_ket_qua'] ?? null;

$stOtt = $khoCaNhan->thongKeOtt(MSSV_DUY);
$tongVan = array_sum($stOtt);
$tiLeThang = $tongVan > 0 ? round($stOtt['thang'] * 100 / $tongVan, 1) : 0.0;
$lichSuOtt = $khoCaNhan->layLichSuOtt(MSSV_DUY);

$dsThoiTiet = $khoCaNhan->thongKeThoiTiet(MSSV_DUY);
$tongCheckIn = array_sum(array_column($dsThoiTiet, 'so_luot'));

$laChuTrang = laChuTrangDuy($khoCaNhan);
$dsMonDaLuu = $khoCaNhan->layMonTheoMssv(MSSV_DUY);

require __DIR__ . '/../../inc/header.php';
?>

<!-- Thanh chuyển ngôn ngữ VI/EN -->
<div class="thanh-ngon-ngu">
    <button type="button" id="nut-doi-ngon-ngu" class="nut-ngon-ngu" aria-label="Chuyển đổi ngôn ngữ">
        <span aria-hidden="true">🌐</span>
        <span id="nhan-ngon-ngu">EN</span>
    </button>
</div>

<!-- Toast flash -->
<?php if ($tb !== ''): ?>
    <div class="thong-bao-thanh-cong" role="status" aria-live="polite">
        <span class="thong-bao-thanh-cong__icon" aria-hidden="true">✔</span>
        <span class="thong-bao-thanh-cong__noi-dung"><?= e($tb) ?></span>
    </div>
<?php endif; ?>

<main class="noi-dung-chinh bao">
    <h1 class="noi-dung-chinh__tieu-de" data-vi="Giới thiệu bản thân" data-en="About me">Giới thiệu bản thân</h1>

    <!-- ĐỒNG HỒ ĐẾM NGƯỢC TẾT -->
    <section class="the-noi-dung dong-ho-tet" aria-labelledby="tieu-de-dong-ho">
        <h2 id="tieu-de-dong-ho" class="the-noi-dung__tieu-de" data-vi="🎊 Đếm ngược đến Tết Nguyên Đán" data-en="🎊 Countdown to Lunar New Year">🎊 Đếm ngược đến Tết Nguyên Đán</h2>
        <p class="dong-ho-tet__mo-ta" data-vi="Tết Đinh Mùi 2027 — Mùng 1 Tết: 06/02/2027" data-en="Year of the Goat 2027 — Lunar New Year: Feb 6, 2027">Tết Đinh Mùi 2027 — Mùng 1 Tết: 06/02/2027</p>
        <div id="dong-ho" class="dong-ho-tet__khung" role="timer" aria-live="polite" aria-atomic="true">
            <p class="dong-ho-tet__cho" data-vi="Đang tải đồng hồ..." data-en="Loading countdown...">Đang tải đồng hồ...</p>
        </div>
    </section>

    <!-- THÔNG TIN CÁ NHÂN -->
    <section class="the-noi-dung thong-tin" aria-labelledby="tieu-de-thong-tin">
        <h2 id="tieu-de-thong-tin" class="the-noi-dung__tieu-de" data-vi="Thông tin cá nhân" data-en="Personal Information">Thông tin cá nhân</h2>
        <p data-vi="Xin chào! Mình là Dương Bảo Duy, thành viên của Nhóm 3 thực hiện dự án EduGPA." data-en="Hi! I'm Duong Bao Duy...">Xin chào! Mình là Dương Bảo Duy, thành viên của Nhóm 3 thực hiện dự án EduGPA.</p>
        <p data-vi="Mình hiện đang học tại lớp 24CNTT1..." data-en="I'm currently studying in class 24CNTT1...">Mình hiện đang học tại lớp 24CNTT1, khoa Toán - Tin, trường Đại học Sư Phạm, Đại học Đà Nẵng. Mình là một người trẻ đang theo đuổi lĩnh vực Công nghệ Thông tin với niềm yêu thích đặc biệt dành cho lập trình, phát triển Web, IoT và các hệ thống công nghệ thực tế.</p>
        <figure class="thong-tin__anh">
            <img src="images/anh-ca-nhan.jpg" alt="Ảnh chân dung của Dương Bảo Duy" width="300" height="400" class="thong-tin__anh-chan-dung" loading="lazy">
            <figcaption class="thong-tin__chu-thich" data-vi="Dương Bảo Duy — MSSV 3120224034" data-en="Duong Bao Duy — ID 3120224034">Dương Bảo Duy — MSSV 3120224034</figcaption>
        </figure>
    </section>

    <!-- CHỨC NĂNG 1: MÁY CHỌN MÓN ĂN -->
    <section id="chon-mon" class="the-noi-dung chon-mon" aria-labelledby="tieu-de-chon-mon">
        <h2 id="tieu-de-chon-mon" class="the-noi-dung__tieu-de" data-vi="🍜 Máy chọn món ăn hôm nay" data-en="🍜 Today's food picker">🍜 Máy chọn món ăn hôm nay</h2>
        <p data-vi="Không biết ăn gì? Paste danh sách món vào ô bên dưới, chọn bữa, rồi để máy quyết định!" data-en="Don't know what to eat?...">Không biết ăn gì? Paste danh sách món vào ô bên dưới, chọn bữa, rồi để máy quyết định!</p>
        <p>Danh sách bạn nhập được giữ riêng trong phiên này. Chỉ chủ trang đã đăng nhập mới lưu thêm món vào danh sách dùng chung.</p>

        <form method="post" action="gioithieu.php" id="form-chon-mon" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="hanh_dong" value="chon_mon">
            <p>
                <label for="dsMon">Danh sách món (2–50 món khác nhau, mỗi dòng 1 món, tối đa 100 ký tự/món):</label>
                <textarea id="dsMon" name="dsMon" rows="6" maxlength="3000"><?= e(implode("\n", $dsMonNhap)) ?></textarea>
            </p>
            <p>
                <label for="bua">Bữa:</label>
                <select id="bua" name="bua">
                    <?php foreach ($buaChoPhep as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="chon-mon__nut"><button type="submit">🎲 Chọn món cho tôi!</button></p>
        </form>

        <div id="chon-mon-ket-qua">
            <?php if ($ketQuaMon !== null): ?>
                <div class="chon-mon__ket-qua" role="status" aria-live="polite">
                    <p class="chon-mon__nhan">Hôm nay ăn gì?</p>
                    <p class="chon-mon__mon"><?= e((string) $ketQuaMon['mon']) ?></p>
                    <p class="chon-mon__meta">Bữa: <strong><?= e($buaChoPhep[$ketQuaMon['bua']] ?? $ketQuaMon['bua']) ?></strong> — lúc <?= e((string) $ketQuaMon['thoiGian']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($dsMonDaLuu !== []): ?>
            <div class="chon-mon__da-luu">
                <h3>Danh sách món đã lưu dùng chung</h3>
                <ul class="chon-mon__lich-su">
                    <?php foreach ($dsMonDaLuu as $monDaLuu): ?>
                        <li>
                            <span><?= e((string) $monDaLuu['ten_mon']) ?> — bữa <?= e($buaChoPhep[$monDaLuu['bua']] ?? $monDaLuu['bua']) ?></span>
                            <?php if ($laChuTrang): ?>
                                <form method="post" action="gioithieu.php#chon-mon" class="chon-mon__xoa">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="hanh_dong" value="xoa_mon">
                                    <input type="hidden" name="id" value="<?= e((int) $monDaLuu['id']) ?>">
                                    <button type="submit" class="nut-phu" data-xac-nhan="Xóa món này khỏi danh sách dùng chung?">Xóa</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="chon-mon__phu"><?= $laChuTrang
                    ? 'Bạn là chủ trang nên có thể xóa từng món dùng chung.'
                    : 'Chỉ chủ trang đã đăng nhập mới xóa được món dùng chung.' ?></p>
            </div>
        <?php endif; ?>
    </section>

    <!-- CHỨC NĂNG 2: OẲN TÙ TÌ -->
    <section id="oan-tu-ti" class="the-noi-dung oan-tu-ti" aria-labelledby="tieu-de-ott">
        <h2 id="tieu-de-ott" class="the-noi-dung__tieu-de" data-vi="✊✋✌️ Oẳn tù tì vs Máy" data-en="✊✋✌️ Rock Paper Scissors">✊✋✌️ Oẳn tù tì vs Máy</h2>
        <p data-vi="Bạn chọn Kéo / Búa / Bao, máy random. Ai thắng nhiều hơn?" data-en="Choose Rock / Paper / Scissors...">Bạn chọn Kéo / Búa / Bao, máy random. Ai thắng nhiều hơn?</p>

        <form method="post" action="gioithieu.php" class="oan-tu-ti__nut" id="form-oan-tu-ti">
            <?= csrfField() ?>
            <input type="hidden" name="hanh_dong" value="choi_oan_tu_ti">

            <button type="submit" name="chon" value="keo" class="nut-ott nut-ott--keo">✊<br>Kéo</button>
            <button type="submit" name="chon" value="bua" class="nut-ott nut-ott--bua">✋<br>Búa</button>
            <button type="submit" name="chon" value="bao" class="nut-ott nut-ott--bao">✌️<br>Bao</button>
        </form>

        <div id="oan-tu-ti-vua">
            <?php if (!empty($lichSuOtt)): $vua = $lichSuOtt[0]; ?>
                <div class="oan-tu-ti__ket-qua oan-tu-ti__ket-qua--<?= e($vua['ket_qua']) ?>" role="status" aria-live="polite">
                    <p>Bạn: <strong><?= e($labelChon[$vua['nguoi_chon']] ?? $vua['nguoi_chon']) ?></strong> &nbsp;vs&nbsp; Máy: <strong><?= e($labelChon[$vua['may_chon']] ?? $vua['may_chon']) ?></strong></p>
                    <p class="oan-tu-ti__vua"><?= e($labelKetQua[$vua['ket_qua']] ?? $vua['ket_qua']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div id="oan-tu-ti-thong-ke">
            <div class="oan-tu-ti__thong-ke">
                <h3>Thống kê (Lưu DB)</h3>
                <ul>
                    <li>🎉 Thắng: <strong><?= (int)$stOtt['thang'] ?></strong></li>
                    <li>😢 Thua: <strong><?= (int)$stOtt['thua'] ?></strong></li>
                    <li>🤝 Hoà: <strong><?= (int)$stOtt['hoa'] ?></strong></li>
                    <li>Tổng: <strong><?= (int)$tongVan ?></strong> ván — Tỉ lệ thắng: <strong><?= $tiLeThang ?>%</strong></li>
                </ul>
                <?php if ($laChuTrang && $tongVan > 0): ?>
                    <form method="post" action="gioithieu.php#oan-tu-ti" class="oan-tu-ti__xoa">
                        <?= csrfField() ?>
                        <input type="hidden" name="hanh_dong" value="reset_oan_tu_ti">
                        <button type="submit" class="nut-phu" data-xac-nhan="Xóa toàn bộ lịch sử oẳn tù tì của trang?">Xóa lịch sử oẳn tù tì</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div id="oan-tu-ti-lich-su">
            <?php if ($lichSuOtt): ?>
                <h3>10 ván gần nhất</h3>
                <table class="oan-tu-ti__bang">
                    <thead><tr><th>Thời gian</th><th>Bạn</th><th>Máy</th><th>Kết quả</th></tr></thead>
                    <tbody>
                        <?php foreach ($lichSuOtt as $v): ?>
                            <tr>
                                <td><?= e((string) $v['thoi_gian']) ?></td>
                                <td><?= e($labelChon[$v['nguoi_chon']] ?? $v['nguoi_chon']) ?></td>
                                <td><?= e($labelChon[$v['may_chon']] ?? $v['may_chon']) ?></td>
                                <td><?= e($labelKetQua[$v['ket_qua']] ?? $v['ket_qua']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <!-- CHỨC NĂNG 3: CHECK-IN THỜI TIẾT -->
    <section id="thoi-tiet" class="the-noi-dung thoi-tiet" aria-labelledby="tieu-de-thoi-tiet">
        <h2 id="tieu-de-thoi-tiet" class="the-noi-dung__tieu-de">🌤️ Check-in Thời tiết hôm nay</h2>
        <p>Hôm nay chỗ bạn thời tiết thế nào? Bấm chọn để cùng mọi người cập nhật nhé!</p>

        <form method="post" action="gioithieu.php#thoi-tiet" class="thoi-tiet__nut">
            <?= csrfField() ?>
            <input type="hidden" name="hanh_dong" value="check_in_thoi_tiet">

            <button type="submit" name="trang_thai" value="nang" class="thoi-tiet__nut-bam thoi-tiet__nut-bam--nang">☀️ Nắng</button>
            <button type="submit" name="trang_thai" value="mua" class="thoi-tiet__nut-bam thoi-tiet__nut-bam--mua">🌧️ Mưa</button>
            <button type="submit" name="trang_thai" value="may" class="thoi-tiet__nut-bam thoi-tiet__nut-bam--may">☁️ Nhiều mây</button>
            <button type="submit" name="trang_thai" value="giong_bao" class="thoi-tiet__nut-bam thoi-tiet__nut-bam--giong-bao">⛈️ Giông bão</button>
        </form>

        <div class="thoi-tiet__thong-ke">
            <h3>Thống kê thời tiết từ CSDL</h3>
            <?php if ($tongCheckIn === 0): ?>
                <p>Chưa có ai check-in. Hãy là người đầu tiên!</p>
            <?php else: ?>
                <p>Tổng cộng: <strong><?= e((int) $tongCheckIn) ?></strong> lượt check-in</p>
                <ul class="thoi-tiet__danh-sach">
                    <?php foreach ($dsThoiTiet as $tt):
                        $phanTram = round(($tt['so_luot'] / $tongCheckIn) * 100, 1);
                    ?>
                        <li class="thoi-tiet__muc">
                            <div class="thoi-tiet__hang">
                                <span><?= e($labelThoiTiet[$tt['trang_thai']] ?? $tt['trang_thai']) ?> (<?= e((int) $tt['so_luot']) ?>)</span>
                                <strong><?= e($phanTram) ?>%</strong>
                            </div>
                            <div class="thoi-tiet__thanh">
                                <div class="thoi-tiet__thanh-do" data-phan-tram="<?= e($phanTram) ?>"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <!-- DỰ ÁN VÀ SỞ THÍCH -->
    <section class="the-noi-dung khoi-du-an" aria-labelledby="tieu-de-du-an">
        <h2 id="tieu-de-du-an" class="the-noi-dung__tieu-de" data-vi="Dự án và sở thích" data-en="Projects & Hobbies">Dự án và sở thích</h2>
        <p data-vi="Mình không chỉ thích học kiến thức từ sách vở..." data-en="I don't just like learning from books...">Mình không chỉ thích học kiến thức từ sách vở mà còn thích tự tay biến những gì mình học được thành các sản phẩm có thể chạy, có thể tương tác và giải quyết một vấn đề cụ thể. Trong quá trình học tập, mình đã tìm hiểu và thực hành nhiều lĩnh vực khác nhau như lập trình C/C++, Java, Python, phát triển Web, Cơ sở dữ liệu, Mạng máy tính, Hệ điều hành và Internet of Things.</p>
        <blockquote class="gioi-thieu__trich-dan">
            <p data-vi='"Không cần phải biết tất cả ngay từ đầu..."' data-en='"You don&apos;t need to know everything at once..."'>"Không cần phải biết tất cả ngay từ đầu. Quan trọng là dám bắt đầu và tiếp tục tìm cách giải quyết vấn đề."</p>
        </blockquote>
        <p data-vi="Ngoài việc học tập, mình thích chơi thể thao..." data-en="Besides studying...">Ngoài việc học tập, mình thích chơi thể thao, đọc truyện và tham gia các hoạt động, sự kiện do đoàn trường tổ chức.</p>
        <div class="gioi-thieu__du-an-anh">
            <figure class="gioi-thieu__anh-doc">
                <img src="images/du-an-1.jpg" alt="Dự án IoT sử dụng Arduino và Module Bluetooth" width="400" height="600" loading="lazy">
                <figcaption data-vi="Dự án IoT với Arduino & Module Bluetooth" data-en="IoT project with Arduino & Bluetooth Module">Dự án IoT với Arduino &amp; Module Bluetooth</figcaption>
            </figure>
            <figure class="gioi-thieu__anh-doc">
                <img src="images/thethao.jpg" alt="Thể thao" width="400" height="600" loading="lazy">
                <figcaption data-vi="Thể thao" data-en="Sports">Thể thao</figcaption>
            </figure>
        </div>
    </section>

    <!-- KỸ NĂNG -->
    <section class="the-noi-dung ky-nang" aria-labelledby="tieu-de-ky-nang">
        <h2 id="tieu-de-ky-nang" class="the-noi-dung__tieu-de" data-vi="Kỹ năng" data-en="Skills">Kỹ năng</h2>
        <ul class="ky-nang__danh-sach">
            <li data-vi="Lập trình C/C++, Java, Python" data-en="C/C++, Java, Python programming">Lập trình C/C++, Java, Python</li>
            <li data-vi="Phát triển Web (HTML5, CSS3, JavaScript)" data-en="Web Development">Phát triển Web (HTML5, CSS3, JavaScript)</li>
            <li data-vi="IoT và hệ thống nhúng (ESP32, MQTT)" data-en="IoT & Embedded Systems">IoT và hệ thống nhúng (ESP32, MQTT)</li>
            <li data-vi="Cơ sở dữ liệu" data-en="Databases">Cơ sở dữ liệu</li>
            <li data-vi="Mạng máy tính và Hệ điều hành" data-en="Computer Networks & Operating Systems">Mạng máy tính và Hệ điều hành</li>
            <li data-vi="Quản lý mã nguồn với GitHub" data-en="Source control with GitHub">Quản lý mã nguồn với GitHub</li>
            <li data-vi="Làm việc nhóm" data-en="Teamwork">Làm việc nhóm</li>
        </ul>
    </section>

    <!-- LIÊN HỆ -->
    <section class="the-noi-dung lien-he" aria-labelledby="tieu-de-lien-he">
        <h2 id="tieu-de-lien-he" class="the-noi-dung__tieu-de" data-vi="Liên hệ" data-en="Contact">Liên hệ</h2>
        <ul class="lien-he__danh-sach">
            <li><strong data-vi="Email:" data-en="Email:">Email:</strong> <a href="mailto:baoduykslt@gmail.com">baoduykslt@gmail.com</a></li>
            <li><strong data-vi="GitHub:" data-en="GitHub:">GitHub:</strong> <a href="https://github.com/duongbaoduy" target="_blank" rel="noopener noreferrer">github.com/duongbaoduy</a></li>
            <li><strong data-vi="Facebook:" data-en="Facebook:">Facebook:</strong> <a href="https://facebook.com/duongbaoduy" target="_blank" rel="noopener noreferrer">facebook.com/duongbaoduy</a></li>
        </ul>
    </section>

    <!-- THỜI KHÓA BIỂU -->
    <section class="the-noi-dung lich-hoc" aria-labelledby="tieu-de-tkb">
        <h2 id="tieu-de-tkb" class="the-noi-dung__tieu-de" data-vi="Thời khóa biểu trong tuần" data-en="Weekly Schedule">Thời khóa biểu trong tuần</h2>
        <p class="lich-hoc__huong-dan" data-vi="Kéo ngang hoặc dùng phím mũi tên để xem đủ 7 ngày" data-en="Scroll horizontally or use arrow keys">Kéo ngang hoặc dùng phím mũi tên để xem đủ 7 ngày</p>
        
        <div class="tkb-container" tabindex="0" role="region" aria-label="Bảng thời khóa biểu có thể cuộn ngang">
            <table class="thoi-khoa-bieu">
                <caption data-vi="Thời khóa biểu tuần" data-en="Weekly Timetable">Thời khóa biểu tuần</caption>
                <thead>
                    <tr>
                        <th scope="col" data-vi="Tiết" data-en="Period">Tiết</th>
                        <th scope="col" data-vi="Thứ 2" data-en="Mon">Thứ 2</th>
                        <th scope="col" data-vi="Thứ 3" data-en="Tue">Thứ 3</th>
                        <th scope="col" data-vi="Thứ 4" data-en="Wed">Thứ 4</th>
                        <th scope="col" data-vi="Thứ 5" data-en="Thu">Thứ 5</th>
                        <th scope="col" data-vi="Thứ 6" data-en="Fri">Thứ 6</th>
                        <th scope="col" data-vi="Thứ 7" data-en="Sat">Thứ 7</th>
                        <th scope="col" data-vi="Chủ nhật" data-en="Sun">Chủ nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" class="tiet">1</th>
                        <td></td><td></td><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31231398 - 24-0101<br>Lập trình mạng<br>Phòng: A5-403</td>
                        <td rowspan="3" class="mon-hoc">31231755 - 24-0102<br>Thiết kế và lập trình web<br>Phòng: B3-303</td>
                        <td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">2</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">3</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr>
                        <th scope="row" class="tiet">4</th>
                        <td></td><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31231330 - 24-0102<br>Khai phá dữ liệu<br>Phòng: A5-404B</td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">5</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">6</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                </tbody>
                <tbody><tr><td class="nghi" colspan="8"></td></tr></tbody>
                <tbody>
                    <tr>
                        <th scope="row" class="tiet">7</th>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31241283 - 24-0301<br>Hệ quản trị cơ sở dữ liệu<br>Phòng: B1-104</td>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">21221904 - 24-0318<br>Lịch sử Đảng Cộng sản Việt Nam<br>Phòng: A6-502</td>
                        <td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">8</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">9</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr>
                        <th scope="row" class="tiet">10</th>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31231016 - 24-0103<br>Công nghệ phần mềm<br>Phòng: B3-304</td>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31221010 - 24-0103<br>An toàn thông tin<br>Phòng: B3-506</td>
                        <td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">11</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">12</th><td></td><td></td><td></td><td></td><td></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>



<?php require __DIR__ . '/../../inc/footer.php'; ?>
