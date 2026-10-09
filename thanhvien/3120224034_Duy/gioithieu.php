<?php
/**
 * Tệp: thanhvien/3120224034_Duy/gioithieu.php (Duy — Phần B)
 * Trang cá nhân Dương Bảo Duy — dùng chung header/footer của nhóm.
 * Chức năng 1: Máy chọn món ăn hôm nay (POST + PRG, session). Có AJAX.
 * Chức năng 2: Oẳn tù tì vs Máy (POST + PRG, session, thống kê). Có AJAX.
 * Fallback: tắt JS vẫn chạy bình thường qua POST thường.
 * Thử: /thanhvien/3120224034_Duy/gioithieu.php
 */
declare(strict_types=1);
require __DIR__ . '/../../inc/config.php';

$goc      = '../../';
$tieuDe   = 'Giới thiệu — Dương Bảo Duy';
$trang    = '';
$cssTrang = 'css/canhan.css';
$lopBody  = 'trang-ca-nhan';

$hanhDong = (string) ($_POST['hanh_dong'] ?? '');

// Phát hiện AJAX (JS gửi kèm header X-Requested-With)
$laAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

/** Trả JSON rồi dừng. */
function traJson(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// CHỨC NĂNG 1 — MÁY CHỌN MÓN ĂN
// ============================================================
$monMacDinh = [
    'Phở bò','Phở gà','Bún bò Huế','Bún chả','Bún riêu','Bánh mì thịt','Bánh mì trứng',
    'Cơm tấm','Cơm gà','Cơm rang','Cơm chiên','Xôi gà','Xôi đậu','Cháo lòng','Cháo cá',
    'Mì Quảng','Cao lầu','Bánh canh','Bánh cuốn','Bánh xèo','Gỏi cuốn','Nem nướng',
    'Bún thịt nướng','Bún đậu mắm tôm','Hủ tiếu','Mì xào','Miến gà','Lẩu Thái',
    'Cơm niêu','Cơm hến'
];
$buaChoPhep = [
    'bat_ky' => 'Bất kỳ', 'sang' => 'Sáng', 'trua' => 'Trưa',
    'toi'    => 'Tối',    'dem'  => 'Đêm',
];

$loiMon    = [];
$dsMonNhap = $_SESSION['duy_mon_an_list'] ?? [];
$ketQuaMon = $_SESSION['duy_mon_an_ket_qua'] ?? null;
$lichSuMon = $_SESSION['duy_mon_an_lich_su'] ?? [];

// ============================================================
// CHỨC NĂNG 2 — OẲN TÙ TÌ
// ============================================================
$labelChon   = ['keo' => '✊ Kéo', 'bua' => '✋ Búa', 'bao' => '✌️ Bao'];
$labelKetQua = ['thang' => '🎉 Thắng', 'thua' => '😢 Thua', 'hoa' => '🤝 Hoà'];

// ============================================================
// XỬ LÝ POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- Chức năng 1: nạp món mặc định ----------
    if ($hanhDong === 'dung_mac_dinh') {
        $_SESSION['duy_mon_an_list'] = $monMacDinh;
        $_SESSION['flash_duy'] = 'Đã nạp 30 món Việt mặc định!';
        header('Location: gioithieu.php#chon-mon');
        exit;
    }

    // ---------- Chức năng 1: xoá lịch sử ----------
    if ($hanhDong === 'xoa_lich_su_mon') {
        unset($_SESSION['duy_mon_an_lich_su']);
        $_SESSION['flash_duy'] = 'Đã xoá lịch sử chọn món.';
        header('Location: gioithieu.php#chon-mon');
        exit;
    }

    // ---------- Chức năng 1: chọn món ----------
    if ($hanhDong === 'chon_mon') {
        $dsMonText = trim((string) ($_POST['dsMon'] ?? ''));
        $bua       = (string) ($_POST['bua'] ?? 'bat_ky');

        $mangMon = array_values(array_filter(
            array_map('trim', explode("\n", $dsMonText)),
            static fn(string $m): bool => $m !== ''
        ));

        if (count($mangMon) < 2) {
            $loiMon['dsMon'] = 'Cần ít nhất 2 món (mỗi dòng 1 món).';
        } elseif (count($mangMon) > 50) {
            $loiMon['dsMon'] = 'Tối đa 50 món.';
        } else {
            foreach ($mangMon as $m) {
                if (mb_strlen($m) > 50) {
                    $loiMon['dsMon'] = 'Mỗi tên món tối đa 50 ký tự.';
                    break;
                }
            }
        }

        if (!isset($buaChoPhep[$bua])) {
            $loiMon['bua'] = 'Bữa không hợp lệ.';
        }

        if ($laAjax && $loiMon) {
            traJson(['ok' => false, 'loi' => $loiMon]);
        }

        if (!$loiMon) {
            $monChon  = $mangMon[array_rand($mangMon)];
            $thoiGian = date('H:i:s d/m/Y');

            $_SESSION['duy_mon_an_list']    = $mangMon;
            $_SESSION['duy_mon_an_ket_qua'] = [
                'mon' => $monChon, 'bua' => $bua, 'thoiGian' => $thoiGian,
            ];

            $ls = $_SESSION['duy_mon_an_lich_su'] ?? [];
            array_unshift($ls, ['mon' => $monChon, 'bua' => $bua, 'thoiGian' => $thoiGian]);
            $ls = array_slice($ls, 0, 5);
            $_SESSION['duy_mon_an_lich_su'] = $ls;

            if ($laAjax) {
                traJson([
                    'ok'       => true,
                    'mon'      => $monChon,
                    'buaLabel' => $buaChoPhep[$bua] ?? $bua,
                    'thoiGian' => $thoiGian,
                    'lichSu'   => array_map(
                        static fn(array $m): array => [
                            'mon'      => $m['mon'],
                            'buaLabel' => $buaChoPhep[$m['bua']] ?? $m['bua'],
                            'thoiGian' => $m['thoiGian'],
                        ],
                        $ls
                    ),
                ]);
            }

            header('Location: gioithieu.php#chon-mon');
            exit;
        }

        $dsMonNhap = $mangMon;
    }

    // ---------- Chức năng 2: chơi oẳn tù tì ----------
    if ($hanhDong === 'choi_oan_tu_ti') {
        $nguoiChon = (string) ($_POST['chon'] ?? '');
        $hopLe = ['keo', 'bua', 'bao'];

        if (!in_array($nguoiChon, $hopLe, true)) {
            if ($laAjax) {
                traJson(['ok' => false, 'loi' => ['chon' => 'Lựa chọn không hợp lệ.']]);
            }
            $_SESSION['flash_duy'] = 'Lựa chọn không hợp lệ, chơi lại nhé!';
            header('Location: gioithieu.php#oan-tu-ti');
            exit;
        }

        $mayChon   = $hopLe[array_rand($hopLe)];
        $luatThang = ['keo' => 'bao', 'bua' => 'keo', 'bao' => 'bua'];

        if ($nguoiChon === $mayChon) {
            $ketQua = 'hoa';
        } elseif ($luatThang[$nguoiChon] === $mayChon) {
            $ketQua = 'thang';
        } else {
            $ketQua = 'thua';
        }

        if (!isset($_SESSION['duy_oan_tu_ti']) || !is_array($_SESSION['duy_oan_tu_ti'])) {
            $_SESSION['duy_oan_tu_ti'] = [
                'thang' => 0, 'thua' => 0, 'hoa' => 0, 'lich_su' => [],
            ];
        }

        $st = &$_SESSION['duy_oan_tu_ti'];
        $st[$ketQua]++;
        array_unshift($st['lich_su'], [
            'nguoi'    => $nguoiChon,
            'may'      => $mayChon,
            'ketQua'   => $ketQua,
            'thoiGian' => date('H:i:s d/m/Y'),
        ]);
        $st['lich_su'] = array_slice($st['lich_su'], 0, 10);

        if ($laAjax) {
            $tongVan = $st['thang'] + $st['thua'] + $st['hoa'];
            $tiLe    = $tongVan > 0 ? round($st['thang'] * 100 / $tongVan, 1) : 0.0;

            traJson([
                'ok' => true,
                'vua' => [
                    'nguoiLabel'  => $labelChon[$nguoiChon] ?? $nguoiChon,
                    'mayLabel'    => $labelChon[$mayChon] ?? $mayChon,
                    'ketQua'      => $ketQua,
                    'ketQuaLabel' => $labelKetQua[$ketQua] ?? $ketQua,
                ],
                'thongKe' => [
                    'thang' => $st['thang'],
                    'thua'  => $st['thua'],
                    'hoa'   => $st['hoa'],
                    'tong'  => $tongVan,
                    'tiLe'  => $tiLe,
                ],
                'lichSu' => array_map(
                    static fn(array $v): array => [
                        'thoiGian'    => $v['thoiGian'],
                        'nguoiLabel'  => $labelChon[$v['nguoi']] ?? $v['nguoi'],
                        'mayLabel'    => $labelChon[$v['may']] ?? $v['may'],
                        'ketQua'      => $v['ketQua'],
                        'ketQuaLabel' => $labelKetQua[$v['ketQua']] ?? $v['ketQua'],
                    ],
                    $st['lich_su']
                ),
            ]);
        }

        header('Location: gioithieu.php#oan-tu-ti');
        exit;
    }

    // ---------- Chức năng 2: reset thống kê ----------
    if ($hanhDong === 'reset_oan_tu_ti') {
        unset($_SESSION['duy_oan_tu_ti']);
        $_SESSION['flash_duy'] = 'Đã reset thống kê oẳn tù tì.';
        header('Location: gioithieu.php#oan-tu-ti');
        exit;
    }
}

// ============================================================
// ĐỌC LẠI DỮ LIỆU HIỂN THỊ
// ============================================================
$tb = $_SESSION['flash_duy'] ?? '';
unset($_SESSION['flash_duy']);

$dsMonNhap = $_SESSION['duy_mon_an_list'] ?? $dsMonNhap;
$ketQuaMon = $_SESSION['duy_mon_an_ket_qua'] ?? null;
$lichSuMon = $_SESSION['duy_mon_an_lich_su'] ?? [];

$stOtt     = $_SESSION['duy_oan_tu_ti'] ?? [
    'thang' => 0, 'thua' => 0, 'hoa' => 0, 'lich_su' => [],
];
$tongVan   = $stOtt['thang'] + $stOtt['thua'] + $stOtt['hoa'];
$tiLeThang = $tongVan > 0 ? round($stOtt['thang'] * 100 / $tongVan, 1) : 0.0;

require __DIR__ . '/../../inc/header.php';
?>

<!-- Thanh chuyển ngôn ngữ VI/EN -->
<div class="thanh-ngon-ngu">
    <button type="button" id="nut-doi-ngon-ngu" class="nut-ngon-ngu"
            aria-label="Chuyển đổi ngôn ngữ giữa tiếng Việt và tiếng Anh">
        <span aria-hidden="true">🌐</span>
        <span id="nhan-ngon-ngu">EN</span>
    </button>
</div>

<!-- Toast flash -->
<?php if ($tb !== ''): ?>
    <div class="thong-bao-thanh-cong" role="status" aria-live="polite">
        <span class="thong-bao-thanh-cong__icon" aria-hidden="true">✔</span>
        <span class="thong-bao-thanh-cong__noi-dung"><?= e($tb) ?></span>
        <button type="button" class="thong-bao-thanh-cong__dong"
                aria-label="Đóng thông báo">×</button>
    </div>
<?php endif; ?>

<main class="noi-dung-chinh bao">

    <h1 class="noi-dung-chinh__tieu-de"
        data-vi="Giới thiệu bản thân"
        data-en="About me">
        Giới thiệu bản thân
    </h1>

    <!-- ============ ĐỒNG HỒ ĐẾM NGƯỢC TẾT ============ -->
    <section class="the-noi-dung dong-ho-tet" aria-labelledby="tieu-de-dong-ho">
        <h2 id="tieu-de-dong-ho" class="the-noi-dung__tieu-de"
            data-vi="🎊 Đếm ngược đến Tết Nguyên Đán"
            data-en="🎊 Countdown to Lunar New Year">
            🎊 Đếm ngược đến Tết Nguyên Đán
        </h2>

        <p class="dong-ho-tet__mo-ta"
           data-vi="Tết Đinh Mùi 2027 — Mùng 1 Tết: 06/02/2027"
           data-en="Year of the Goat 2027 — Lunar New Year: Feb 6, 2027">
            Tết Đinh Mùi 2027 — Mùng 1 Tết: 06/02/2027
        </p>

        <div id="dong-ho" class="dong-ho-tet__khung"
             role="timer" aria-live="polite" aria-atomic="true">
            <p class="dong-ho-tet__cho"
               data-vi="Đang tải đồng hồ..."
               data-en="Loading countdown...">
                Đang tải đồng hồ...
            </p>
        </div>
    </section>

    <!-- ============ THÔNG TIN CÁ NHÂN ============ -->
    <section class="the-noi-dung thong-tin" aria-labelledby="tieu-de-thong-tin">
        <h2 id="tieu-de-thong-tin" class="the-noi-dung__tieu-de"
            data-vi="Thông tin cá nhân"
            data-en="Personal Information">
            Thông tin cá nhân
        </h2>

        <p data-vi="Xin chào! Mình là Dương Bảo Duy, thành viên của Nhóm 3 thực hiện dự án EduGPA."
           data-en="Hi! I'm Duong Bao Duy, a member of Group 3 working on the EduGPA project.">
            Xin chào! Mình là Dương Bảo Duy, thành viên của Nhóm 3 thực hiện dự án EduGPA.
        </p>

        <p data-vi="Mình hiện đang học tại lớp 24CNTT1, khoa Toán - Tin, trường Đại học Sư Phạm, Đại học Đà Nẵng. Mình là một người trẻ đang theo đuổi lĩnh vực Công nghệ Thông tin với niềm yêu thích đặc biệt dành cho lập trình, phát triển Web, IoT và các hệ thống công nghệ thực tế."
           data-en="I'm currently studying in class 24CNTT1, Faculty of Mathematics and Informatics, University of Science and Education, The University of Da Nang. I'm a young person pursuing Information Technology with a special passion for programming, Web development, IoT and real-world technology systems.">
            Mình hiện đang học tại lớp 24CNTT1, khoa Toán - Tin, trường Đại học Sư Phạm, Đại học Đà Nẵng. Mình là một người trẻ đang theo đuổi lĩnh vực Công nghệ Thông tin với niềm yêu thích đặc biệt dành cho lập trình, phát triển Web, IoT và các hệ thống công nghệ thực tế.
        </p>

        <figure class="thong-tin__anh">
            <img src="images/anh-ca-nhan.jpg"
                 alt="Ảnh chân dung của Dương Bảo Duy"
                 width="300" height="400"
                 class="thong-tin__anh-chan-dung"
                 loading="lazy">
            <figcaption class="thong-tin__chu-thich"
                        data-vi="Dương Bảo Duy — MSSV 3120224034"
                        data-en="Duong Bao Duy — ID 3120224034">
                Dương Bảo Duy — MSSV 3120224034
            </figcaption>
        </figure>
    </section>

    <!-- ==================================================
         CHỨC NĂNG 1 — MÁY CHỌN MÓN ĂN
         ================================================== -->
    <section id="chon-mon" class="the-noi-dung chon-mon" aria-labelledby="tieu-de-chon-mon">
        <h2 id="tieu-de-chon-mon" class="the-noi-dung__tieu-de"
            data-vi="🍜 Máy chọn món ăn hôm nay"
            data-en="🍜 Today's food picker">
            🍜 Máy chọn món ăn hôm nay
        </h2>

        <p data-vi="Không biết ăn gì? Paste danh sách món vào ô bên dưới, chọn bữa, rồi để máy quyết định!"
           data-en="Don't know what to eat? Paste your dish list below, choose a meal, let the machine decide!">
            Không biết ăn gì? Paste danh sách món vào ô bên dưới, chọn bữa, rồi để máy quyết định!
        </p>

        <form method="post" action="gioithieu.php" id="form-chon-mon" novalidate>
            <input type="hidden" name="hanh_dong" value="chon_mon">

            <p>
                <label for="dsMon">Danh sách món (mỗi dòng 1 món, 2–50 món):</label>
                <textarea id="dsMon" name="dsMon" rows="6" maxlength="3000"
                          placeholder="Phở bò&#10;Cơm tấm&#10;Bún chả..."><?= e(implode("\n", $dsMonNhap)) ?></textarea>
                <?php if (!empty($loiMon['dsMon'])): ?>
                    <small class="loi-bieu-mau"><?= e($loiMon['dsMon']) ?></small>
                <?php endif; ?>
            </p>

            <p>
                <label for="bua">Bữa:</label>
                <select id="bua" name="bua">
                    <?php foreach ($buaChoPhep as $key => $label): ?>
                        <option value="<?= e($key) ?>"
                            <?= (($_POST['bua'] ?? 'bat_ky') === $key) ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($loiMon['bua'])): ?>
                    <small class="loi-bieu-mau"><?= e($loiMon['bua']) ?></small>
                <?php endif; ?>
            </p>

            <p class="chon-mon__nut">
                <button type="submit">🎲 Chọn món cho tôi!</button>
            </p>
        </form>

        <form method="post" action="gioithieu.php" class="chon-mon__phu">
            <input type="hidden" name="hanh_dong" value="dung_mac_dinh">
            <button type="submit" class="nut-phu">📋 Nạp 30 món Việt mặc định</button>
        </form>

        <div id="chon-mon-ket-qua">
            <?php if ($ketQuaMon !== null): ?>
                <div class="chon-mon__ket-qua" role="status" aria-live="polite">
                    <p class="chon-mon__nhan">Hôm nay ăn gì?</p>
                    <p class="chon-mon__mon"><?= e((string) $ketQuaMon['mon']) ?></p>
                    <p class="chon-mon__meta">
                        Bữa: <strong><?= e($buaChoPhep[$ketQuaMon['bua']] ?? $ketQuaMon['bua']) ?></strong>
                        — lúc <?= e((string) $ketQuaMon['thoiGian']) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div id="chon-mon-lich-su">
            <?php if ($lichSuMon): ?>
                <h3>5 lần chọn gần nhất</h3>
                <ul class="chon-mon__lich-su">
                    <?php foreach ($lichSuMon as $m): ?>
                        <li>
                            <strong><?= e((string) $m['mon']) ?></strong>
                            — bữa <?= e($buaChoPhep[$m['bua']] ?? $m['bua']) ?>
                            <em>(<?= e((string) $m['thoiGian']) ?>)</em>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form method="post" action="gioithieu.php" style="margin-top:.75rem;">
                    <input type="hidden" name="hanh_dong" value="xoa_lich_su_mon">
                    <button type="submit" class="nut-phu"
                            onclick="return confirm('Xoá toàn bộ lịch sử chọn món?');">
                        🗑 Xoá lịch sử
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- ==================================================
         CHỨC NĂNG 2 — OẲN TÙ TÌ
         ================================================== -->
    <section id="oan-tu-ti" class="the-noi-dung oan-tu-ti" aria-labelledby="tieu-de-ott">
        <h2 id="tieu-de-ott" class="the-noi-dung__tieu-de"
            data-vi="✊✋✌️ Oẳn tù tì vs Máy"
            data-en="✊✋✌️ Rock Paper Scissors vs Machine">
            ✊✋✌️ Oẳn tù tì vs Máy
        </h2>

        <p data-vi="Bạn chọn Kéo / Búa / Bao, máy random. Ai thắng nhiều hơn?"
           data-en="Choose Rock / Paper / Scissors, machine is random. Who wins more?">
            Bạn chọn Kéo / Búa / Bao, máy random. Ai thắng nhiều hơn?
        </p>

        <form method="post" action="gioithieu.php" class="oan-tu-ti__nut" id="form-oan-tu-ti">
            <input type="hidden" name="hanh_dong" value="choi_oan_tu_ti">
            <button type="submit" name="chon" value="keo" class="nut-ott nut-ott--keo">
                ✊<br>Kéo
            </button>
            <button type="submit" name="chon" value="bua" class="nut-ott nut-ott--bua">
                ✋<br>Búa
            </button>
            <button type="submit" name="chon" value="bao" class="nut-ott nut-ott--bao">
                ✌️<br>Bao
            </button>
        </form>

        <div id="oan-tu-ti-vua">
            <?php if ($stOtt['lich_su']): $vua = $stOtt['lich_su'][0]; ?>
                <div class="oan-tu-ti__ket-qua oan-tu-ti__ket-qua--<?= e($vua['ketQua']) ?>"
                     role="status" aria-live="polite">
                    <p>
                        Bạn: <strong><?= e($labelChon[$vua['nguoi']] ?? $vua['nguoi']) ?></strong>
                        &nbsp;vs&nbsp;
                        Máy: <strong><?= e($labelChon[$vua['may']] ?? $vua['may']) ?></strong>
                    </p>
                    <p class="oan-tu-ti__vua">
                        <?= e($labelKetQua[$vua['ketQua']] ?? $vua['ketQua']) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div id="oan-tu-ti-thong-ke">
            <div class="oan-tu-ti__thong-ke">
                <h3>Thống kê</h3>
                <ul>
                    <li>🎉 Thắng: <strong><?= (int) $stOtt['thang'] ?></strong></li>
                    <li>😢 Thua: <strong><?= (int) $stOtt['thua'] ?></strong></li>
                    <li>🤝 Hoà: <strong><?= (int) $stOtt['hoa'] ?></strong></li>
                    <li>Tổng: <strong><?= (int) $tongVan ?></strong> ván —
                        Tỉ lệ thắng: <strong><?= e((string) $tiLeThang) ?>%</strong></li>
                </ul>

                <?php if ($tongVan > 0): ?>
                    <div class="oan-tu-ti__bar" aria-hidden="true">
                        <div class="oan-tu-ti__bar-thang"
                             style="width: <?= e((string) $tiLeThang) ?>%;"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="oan-tu-ti-lich-su">
            <?php if ($stOtt['lich_su']): ?>
                <h3>10 ván gần nhất</h3>
                <table class="oan-tu-ti__bang">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Bạn</th>
                            <th>Máy</th>
                            <th>Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stOtt['lich_su'] as $v): ?>
                            <tr>
                                <td><?= e((string) $v['thoiGian']) ?></td>
                                <td><?= e($labelChon[$v['nguoi']] ?? $v['nguoi']) ?></td>
                                <td><?= e($labelChon[$v['may']] ?? $v['may']) ?></td>
                                <td><?= e($labelKetQua[$v['ketQua']] ?? $v['ketQua']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="post" action="gioithieu.php" style="margin-top:.75rem;">
                    <input type="hidden" name="hanh_dong" value="reset_oan_tu_ti">
                    <button type="submit" class="nut-phu"
                            onclick="return confirm('Reset toàn bộ thống kê oẳn tù tì?');">
                        🔄 Reset thống kê
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============ DỰ ÁN VÀ SỞ THÍCH ============ -->
    <section class="the-noi-dung khoi-du-an" aria-labelledby="tieu-de-du-an">
        <h2 id="tieu-de-du-an" class="the-noi-dung__tieu-de"
            data-vi="Dự án và sở thích"
            data-en="Projects & Hobbies">
            Dự án và sở thích
        </h2>

        <p data-vi="Mình không chỉ thích học kiến thức từ sách vở mà còn thích tự tay biến những gì mình học được thành các sản phẩm có thể chạy, có thể tương tác và giải quyết một vấn đề cụ thể. Trong quá trình học tập, mình đã tìm hiểu và thực hành nhiều lĩnh vực khác nhau như lập trình C/C++, Java, Python, phát triển Web, Cơ sở dữ liệu, Mạng máy tính, Hệ điều hành và Internet of Things."
           data-en="I don't just like learning from books, but also love turning what I learn into working, interactive products that solve specific problems. During my studies, I have explored and practiced many areas such as C/C++, Java, Python, Web development, Databases, Computer Networks, Operating Systems, and the Internet of Things.">
            Mình không chỉ thích học kiến thức từ sách vở mà còn thích tự tay biến những gì mình học được thành các sản phẩm có thể chạy, có thể tương tác và giải quyết một vấn đề cụ thể. Trong quá trình học tập, mình đã tìm hiểu và thực hành nhiều lĩnh vực khác nhau như lập trình C/C++, Java, Python, phát triển Web, Cơ sở dữ liệu, Mạng máy tính, Hệ điều hành và Internet of Things.
        </p>

        <p data-vi="Một trong những điều mình thích nhất là bắt đầu từ một ý tưởng đơn giản rồi từng bước biến nó thành một hệ thống hoàn chỉnh — từ những chương trình nhỏ, website, chatbot cho đến các dự án robot sử dụng ESP32, cảm biến, MQTT và điều khiển từ xa."
           data-en="One of the things I enjoy most is starting from a simple idea and gradually turning it into a complete system — from small programs, websites, chatbots to robot projects using ESP32, sensors, MQTT and remote control.">
            Một trong những điều mình thích nhất là bắt đầu từ một ý tưởng đơn giản rồi từng bước biến nó thành một hệ thống hoàn chỉnh — từ những chương trình nhỏ, website, chatbot cho đến các dự án robot sử dụng ESP32, cảm biến, MQTT và điều khiển từ xa.
        </p>

        <blockquote class="gioi-thieu__trich-dan">
            <p data-vi='"Không cần phải biết tất cả ngay từ đầu. Quan trọng là dám bắt đầu và tiếp tục tìm cách giải quyết vấn đề."'
               data-en='"You don&apos;t need to know everything at once. What matters is daring to start and keep finding ways to solve the problem."'>
                "Không cần phải biết tất cả ngay từ đầu. Quan trọng là dám bắt đầu và tiếp tục tìm cách giải quyết vấn đề."
            </p>
        </blockquote>

        <p data-vi="Ngoài việc học tập, mình thích chơi thể thao, đọc truyện và tham gia các hoạt động, sự kiện do đoàn trường tổ chức."
           data-en="Besides studying, I enjoy playing sports, reading comics and joining activities and events organized by the school union.">
            Ngoài việc học tập, mình thích chơi thể thao, đọc truyện và tham gia các hoạt động, sự kiện do đoàn trường tổ chức.
        </p>

        <div class="gioi-thieu__du-an-anh">
            <figure class="gioi-thieu__anh-doc">
                <img src="images/du-an-1.jpg"
                     alt="Dự án IoT sử dụng Arduino và Module Bluetooth"
                     width="400" height="600"
                     loading="lazy">
                <figcaption data-vi="Dự án IoT với Arduino & Module Bluetooth"
                            data-en="IoT project with Arduino & Bluetooth Module">
                    Dự án IoT với Arduino &amp; Module Bluetooth
                </figcaption>
            </figure>

            <figure class="gioi-thieu__anh-doc">
                <img src="images/thethao.jpg"
                     alt="Thể thao"
                     width="400" height="600"
                     loading="lazy">
                <figcaption data-vi="Thể thao" data-en="Sports">Thể thao</figcaption>
            </figure>
        </div>
    </section>

    <!-- ============ KỸ NĂNG ============ -->
    <section class="the-noi-dung ky-nang" aria-labelledby="tieu-de-ky-nang">
        <h2 id="tieu-de-ky-nang" class="the-noi-dung__tieu-de"
            data-vi="Kỹ năng" data-en="Skills">
            Kỹ năng
        </h2>

        <ul class="ky-nang__danh-sach">
            <li data-vi="Lập trình C/C++, Java, Python"
                data-en="C/C++, Java, Python programming">Lập trình C/C++, Java, Python</li>
            <li data-vi="Phát triển Web (HTML5, CSS3, JavaScript)"
                data-en="Web Development (HTML5, CSS3, JavaScript)">Phát triển Web (HTML5, CSS3, JavaScript)</li>
            <li data-vi="IoT và hệ thống nhúng (ESP32, MQTT)"
                data-en="IoT & Embedded Systems (ESP32, MQTT)">IoT và hệ thống nhúng (ESP32, MQTT)</li>
            <li data-vi="Cơ sở dữ liệu" data-en="Databases">Cơ sở dữ liệu</li>
            <li data-vi="Mạng máy tính và Hệ điều hành"
                data-en="Computer Networks & Operating Systems">Mạng máy tính và Hệ điều hành</li>
            <li data-vi="Quản lý mã nguồn với GitHub"
                data-en="Source control with GitHub">Quản lý mã nguồn với GitHub</li>
            <li data-vi="Làm việc nhóm" data-en="Teamwork">Làm việc nhóm</li>
        </ul>
    </section>

    <!-- ============ LIÊN HỆ ============ -->
    <section class="the-noi-dung lien-he" aria-labelledby="tieu-de-lien-he">
        <h2 id="tieu-de-lien-he" class="the-noi-dung__tieu-de"
            data-vi="Liên hệ" data-en="Contact">
            Liên hệ
        </h2>

        <ul class="lien-he__danh-sach">
            <li>
                <strong data-vi="Email:" data-en="Email:">Email:</strong>
                <a href="mailto:baoduykslt@gmail.com">baoduykslt@gmail.com</a>
            </li>
            <li>
                <strong data-vi="GitHub:" data-en="GitHub:">GitHub:</strong>
                <a href="https://github.com/duongbaoduy" target="_blank" rel="noopener noreferrer">
                    github.com/duongbaoduy
                </a>
            </li>
            <li>
                <strong data-vi="Facebook:" data-en="Facebook:">Facebook:</strong>
                <a href="https://facebook.com/duongbaoduy" target="_blank" rel="noopener noreferrer">
                    facebook.com/duongbaoduy
                </a>
            </li>
        </ul>
    </section>

    <!-- ============ THỜI KHÓA BIỂU ============ -->
    <section class="the-noi-dung lich-hoc" aria-labelledby="tieu-de-tkb">
        <h2 id="tieu-de-tkb" class="the-noi-dung__tieu-de"
            data-vi="Thời khóa biểu trong tuần"
            data-en="Weekly Schedule">
            Thời khóa biểu trong tuần
        </h2>

        <p class="lich-hoc__huong-dan"
           data-vi="Kéo ngang hoặc dùng phím mũi tên để xem đủ 7 ngày"
           data-en="Scroll horizontally or use arrow keys to see all 7 days">
            Kéo ngang hoặc dùng phím mũi tên để xem đủ 7 ngày
        </p>

        <div class="tkb-container" tabindex="0" role="region"
             aria-label="Bảng thời khóa biểu có thể cuộn ngang">

            <table class="thoi-khoa-bieu">
                <caption data-vi="Thời khóa biểu tuần" data-en="Weekly Timetable">
                    Thời khóa biểu tuần
                </caption>

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
                    <tr>
                        <th scope="row" class="tiet">2</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">3</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">4</th>
                        <td></td><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31231330 - 24-0102<br>Khai phá dữ liệu<br>Phòng: A5-404B</td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">5</th>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">6</th>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                </tbody>

                <tbody>
                    <tr><td class="nghi" colspan="8"></td></tr>
                </tbody>

                <tbody>
                    <tr>
                        <th scope="row" class="tiet">7</th>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31241283 - 24-0301<br>Hệ quản trị cơ sở dữ liệu<br>Phòng: B1-104</td>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">21221904 - 24-0318<br>Lịch sử Đảng Cộng sản Việt Nam<br>Phòng: A6-502</td>
                        <td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">8</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">9</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">10</th>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31231016 - 24-0103<br>Công nghệ phần mềm<br>Phòng: B3-304</td>
                        <td></td><td></td>
                        <td rowspan="3" class="mon-hoc">31221010 - 24-0103<br>An toàn thông tin<br>Phòng: B3-506</td>
                        <td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">11</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">12</th>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script type="module" src="canhan.js"></script>

<?php require __DIR__ . '/../../inc/footer.php'; ?>