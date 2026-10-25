<?php
declare(strict_types=1);
/**
 * Trang cá nhân Hải: avatar MySQL chỉ chủ trang được đổi, CSRF và PRG 303.
 * Lượt xem theo ngày và thống kê 7 ngày riêng MSSV, qua lớp PDO.
 * Thử khách/tài khoản khác POST trả 403; chủ trang tải ảnh, F5 không gửi lại.
 * HTML và bố cục được kiểm tra ở màn hình 360px.
 */
require_once __DIR__ . '/../../inc/config.php';
$mssvTrangCaNhan = '3120224042';
$thuMucAnh = __DIR__ . '/../../uploads';
$khoAnh = new \App\Data\KhoAnhDaiDien(db());
$ndTrang = nguoiDung();
$uidTrang = (int) ($ndTrang['id'] ?? 0);
$laChuTrang = $uidTrang > 0 && $khoAnh->laChuTrang($mssvTrangCaNhan, $uidTrang);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST'); http_response_code(405); exit('Phương thức không hợp lệ.');
}
if ($method === 'POST') {
    if (!$laChuTrang) { require __DIR__ . '/../../403.php'; exit; }
    $token = $_POST['_csrf'] ?? null;
    if (!is_string($token) || !csrfKiemTra($token)) {
        $_SESSION['loi_avatar'] = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
    } else {
        $tenMoi = null;
        try {
            $file = $_FILES['avatar'] ?? null;
            if (!is_array($file)) throw new RuntimeException('Vui lòng chọn ảnh đại diện.');
            $tenMoi = (new \App\Services\TaiAnh($thuMucAnh))->luu($file);
            if ($tenMoi === null) throw new RuntimeException('Vui lòng chọn ảnh đại diện.');
            $tenCu = $khoAnh->luu($mssvTrangCaNhan, $uidTrang, $tenMoi);
            // Tên do server sinh và đã được kiểm tra trong kho dữ liệu.
            if ($tenCu !== null && $tenCu !== $tenMoi) @unlink($thuMucAnh . '/' . $tenCu);
            $_SESSION['flash_avatar'] = 'Cập nhật ảnh đại diện thành công!';
        } catch (Throwable $err) {
            if ($tenMoi !== null) @unlink($thuMucAnh . '/' . $tenMoi);
            if ($err instanceof PDOException) {
                error_log((string) $err);
                $_SESSION['loi_avatar'] = 'Không lưu được ảnh đại diện, vui lòng thử lại.';
            } elseif ($err instanceof RuntimeException) {
                $_SESSION['loi_avatar'] = $err->getMessage();
            } else throw $err;
        }
    }
    header('Location: gioithieu.php', true, 303); exit;
}
$thongBaoAnh = $_SESSION['flash_avatar'] ?? '';
$loiAnh = $_SESSION['loi_avatar'] ?? '';
unset($_SESSION['flash_avatar'], $_SESSION['loi_avatar']);
$tenAvatar = $khoAnh->lay($mssvTrangCaNhan);
$avatarHienTai = $tenAvatar !== null && is_file($thuMucAnh . '/' . $tenAvatar)
    ? '../../uploads/' . $tenAvatar : 'images/hoanghai.jpg';
$khoLuotXem = new \App\Data\KhoLuotXem(db());
$ngayHomNay = new DateTimeImmutable('today');
$khoLuotXem->ghiNhan($mssvTrangCaNhan, $ngayHomNay->format('Y-m-d'));
$thongKeLuotXem = $khoLuotXem->bayNgay($mssvTrangCaNhan, $ngayHomNay);

// ==========================================
// CHỨC NĂNG 2: MÁY TẠO CHÂM NGÔN IT NGẪU NHIÊN
// ==========================================
$danhSachChamNgon = [
    "Code is like humor. When you have to explain it, it’s bad. – Cory House",
    "First, solve the problem. Then, write the code. – John Johnson",
    "It’s not a bug, it’s an undocumented feature! – Anonymous",
    "Clean code always looks like it was written by someone who cares. – Robert C. Martin",
    "The only way to learn a new programming language is by writing programs in it. – Dennis Ritchie"
];
$chamNgonNgauNhien = $danhSachChamNgon[array_rand($danhSachChamNgon)];

// ==========================================
// CHỨC NĂNG 3: BỘ ĐẾM NGƯỢC NGÀY BẢO VỆ ĐỒ ÁN (DATETIME)
// ==========================================
$ngayBaoVe = new DateTime('2026-10-25 08:00:00');
$ngayHienTai = new DateTime();
$thoiGianConLai = $ngayHienTai->diff($ngayBaoVe);

// 2. Nạp Header
$tieuDe = 'Lê Dương Hoàng Hải | Giới thiệu cá nhân';
$trang  = 'gioi-thieu';
$goc    = '../../';
$cssTrang = 'style.css';
$scriptsTrang = ['thanhvien/3120224042_hai/js/canhan.js'];
require_once __DIR__ . '/../../inc/header.php';
?>

<main class="container">
    
    <!-- CỘT TRÁI: Thông tin cá nhân & Kỹ năng -->
    <section class="profile grid-col">
        <h1>Lê Dương Hoàng Hải</h1>
        
        <!-- HIỂN THỊ ẢNH ĐẠI DIỆN -->
        <img src="<?= e($avatarHienTai) ?>" alt="Ảnh chân dung của Lê Dương Hoàng Hải" width="150" height="150" style="object-fit: cover; border-radius: 5px;">
        
        <!-- FORM ĐỔI ẢNH ĐẠI DIỆN (CHỨC NĂNG 1) -->
        <?php if ($laChuTrang): ?>
        <form action="gioithieu.php" method="post" enctype="multipart/form-data" style="margin: 10px 0; padding: 10px; background: #f4f4f4; border-radius: 5px;">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <label for="avatar" style="font-size: 0.9em; font-weight: bold;">Đổi ảnh đại diện (≤ 2MB):</label><br>
            <input type="file" name="avatar" id="avatar" accept="image/jpeg, image/png, image/webp" required style="font-size: 0.85em; margin: 5px 0;">
            <button type="submit" class="btn-primary" style="padding: 8px 14px; font-size: 0.85em; background-color: #123b6d; color: #ffffff;">Tải lên</button>
            
            <?php if ($thongBaoAnh !== ''): ?>
                <p style="color: green; font-size: 0.85em; margin-top: 5px;"><?= e($thongBaoAnh) ?></p>
            <?php endif; ?>
            <?php if ($loiAnh !== ''): ?>
                <p style="color: red; font-size: 0.85em; margin-top: 5px;"><?= e($loiAnh) ?></p>
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <!-- KHỐI ĐẾM NGƯỢC BẢO VỆ ĐỒ ÁN (CHỨC NĂNG 3) -->
        <div style="margin: 10px 0; padding: 10px 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; font-size: 0.88em; color: #856404;">
            ⏳ <strong>Đếm ngược bảo vệ Đồ án:</strong> 
            <?php if ($ngayHienTai < $ngayBaoVe): ?>
                Còn <strong><?= e($thoiGianConLai->days) ?></strong> ngày <strong><?= e($thoiGianConLai->h) ?></strong> giờ nữa!
            <?php else: ?>
                Đã đến ngày bảo vệ đồ án!
            <?php endif; ?>
        </div>

        <!-- KHỐI CHÂM NGÔN IT NGẪU NHIÊN (CHỨC NĂNG 2) -->
        <div style="margin: 10px 0 15px 0; padding: 10px 12px; background: #eef2ff; border-left: 4px solid #4f46e5; border-radius: 4px; font-style: italic; font-size: 0.88em; color: #3730a3;">
            💡 <strong>Châm ngôn hôm nay:</strong> "<?= e($chamNgonNgauNhien) ?>"
        </div>

        <p>Xin chào! Mình là sinh viên ngành Công nghệ Thông tin tại Khoa Toán - Tin, Trường Đại học Sư phạm - Đại học Đà Nẵng. Mình đam mê phát triển phần mềm và ứng dụng thị giác máy tính.</p>
        
        <div class="like-section">
            <button id="btn-like" class="btn-primary" type="button" style="background-color: #123b6d; color: #ffffff;">
                Ủng hộ Hải: ❤️ <span id="like-count">0</span>
            </button>
        </div>

        <div class="skills-box">
            <h2>Kỹ năng công nghệ:</h2>
            <ul>
                <li><strong>Ngôn ngữ:</strong> C++, Python, SQL.</li>
                <li><strong>Mobile:</strong> Jetpack Compose (Kotlin).</li>
                <li><strong>Computer Vision:</strong> OpenCV, YOLOv8, YOLO-NAS.</li>
                <li><strong>Công cụ:</strong> VS Code, SSMS, Git.</li>
            </ul>
        </div>
    </section>

    <!-- CỘT PHẢI: Sở thích & Thời khóa biểu -->
    <div class="grid-col">
        <article class="hobby-card">
            <h2>Sở thích và Hoạt động</h2>
            <p>Bên cạnh việc nghiên cứu thuật toán, mình rất yêu thích eSports. Mình là fan hâm mộ của T1 và thường chơi Đấu Trường Chân Lý, ARK: Survival Evolved.</p>
            <p>Mình cũng có kinh nghiệm tổ chức và quản lý các giải đấu Liên Quân Mobile cho học sinh, sinh viên tại địa phương.</p>
        </article>

        <div class="table-container">
            <div class="tkb-header">
                <h2 class="tkb-title">Thời khóa biểu</h2>
                <button id="btn-toggle-tkb" class="btn-secondary" type="button">Thu gọn ⬆️</button>
            </div>
            
            <div class="table-wrapper" id="tkb-wrapper" tabindex="0" role="region" aria-label="Bảng thời khóa biểu">
                <table class="tkb-table">
                    <caption>Lịch học hàng tuần của sinh viên Lê Dương Hoàng Hải</caption>
                    <thead>
                        <tr>
                            <th scope="col">Tiết</th>
                            <th scope="col">Thứ 2</th>
                            <th scope="col">Thứ 3</th>
                            <th scope="col">Thứ 4</th>
                            <th scope="col">Thứ 5</th>
                            <th scope="col">Thứ 6</th>
                            <th scope="col">Thứ 7</th>
                            <th scope="col">Chủ nhật</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Tiết 1 -->
                        <tr>
                            <td class="tiet">1</td>
                            <td rowspan="2"><strong>An toàn thông tin</strong><br>Phòng: B3-206</td>
                            <td></td><td></td>
                            <td rowspan="3"><strong>Khai phá dữ liệu</strong><br>Phòng: A5-404B</td>
                            <td rowspan="3"><strong>Lập trình mạng</strong><br>Phòng: B3-303</td>
                            <td rowspan="3"><strong>Thiết kế và lập trình web</strong><br>Phòng: B3-303</td>
                            <td></td>
                        </tr>
                        <!-- Tiết 2 -->
                        <tr>
                            <td class="tiet">2</td><td></td>
                            <td rowspan="3"><strong>Hệ quản trị cơ sở dữ liệu</strong><br>Phòng: B3-402</td><td></td>
                        </tr>
                        <!-- Tiết 3 -->
                        <tr>
                            <td class="tiet">3</td>
                            <td rowspan="2"><strong>Lịch sử Đảng Cộng sản Việt Nam</strong><br>Phòng: A5-404C</td>
                            <td></td><td></td>
                        </tr>
                        <!-- Tiết 4 -->
                        <tr>
                            <td class="tiet">4</td><td></td>
                            <td rowspan="2"><strong>Hệ phân tán</strong><br>Phòng: B3-303</td><td></td><td></td><td></td>
                        </tr>
                        <!-- Tiết 5 -->
                        <tr><td class="tiet">5</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 6 -->
                        <tr><td class="tiet">6</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 7 -->
                        <tr>
                            <td class="tiet">7</td><td></td><td></td><td></td>
                            <td rowspan="3"><strong>Công nghệ phần mềm</strong><br>Phòng: B3-303</td><td></td><td></td><td></td>
                        </tr>
                        <!-- Tiết 8 -->
                        <tr><td class="tiet">8</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 9 -->
                        <tr><td class="tiet">9</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 10 -->
                        <tr><td class="tiet">10</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 11 -->
                        <tr><td class="tiet">11</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 12 -->
                        <tr><td class="tiet">12</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<section class="container" aria-labelledby="tieu-de-luot-xem">
    <h2 id="tieu-de-luot-xem">Lượt xem trang cá nhân trong 7 ngày gần nhất</h2>
    <table>
        <thead><tr><th scope="col">Ngày</th><th scope="col">Tổng lượt xem</th></tr></thead>
        <tbody>
            <?php foreach ($thongKeLuotXem as $dongLuotXem): ?>
                <tr>
                    <td><?= e(date('d/m/Y', strtotime($dongLuotXem['ngay']))) ?></td>
                    <td><?= e($dongLuotXem['so_luot']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php 
// 3. Nạp Footer dùng chung
require_once __DIR__ . '/../../inc/footer.php'; 
?>
