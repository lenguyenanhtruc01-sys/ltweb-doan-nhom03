<?php
// 1. Nạp cấu hình chung từ thư mục gốc
require_once __DIR__ . '/../../inc/config.php';

// Cài đặt múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ==========================================
// CHỨC NĂNG 1: XỬ LÝ UPLOAD ẢNH ĐẠI DIỆN (POST / PRG)
// ==========================================
$thongBaoAnh = $_SESSION['flash_avatar'] ?? '';
$loiAnh      = $_SESSION['loi_avatar'] ?? '';
unset($_SESSION['flash_avatar'], $_SESSION['loi_avatar']);

$thuMucAnh = __DIR__ . '/images/';
if (!is_dir($thuMucAnh)) {
    mkdir($thuMucAnh, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['loi_avatar'] = 'Có lỗi xảy ra khi tải ảnh lên.';
    } elseif ($file['size'] > 2 * 1024 * 1024) { 
        $_SESSION['loi_avatar'] = 'Kích thước ảnh không được vượt quá 2MB.';
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $choPhep = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $choPhep, true)) {
            $_SESSION['loi_avatar'] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $tenMoi = 'avatar_3120224042.' . $ext;
            
            if (move_uploaded_file($file['tmp_name'], $thuMucAnh . $tenMoi)) {
                $_SESSION['flash_avatar'] = 'Cập nhật ảnh đại diện thành công!';
            } else {
                $_SESSION['loi_avatar'] = 'Không thể lưu ảnh vào máy chủ.';
            }
        }
    }
    header('Location: gioithieu.php');
    exit;
}

// Kiểm tra avatar hiện tại
$avatarHienTai = 'hoanghai.jpg'; 
$cacDuoi = ['jpg', 'png', 'webp', 'jpeg'];
foreach ($cacDuoi as $d) {
    if (file_exists($thuMucAnh . 'avatar_3120224042.' . $d)) {
        $avatarHienTai = 'avatar_3120224042.' . $d;
        break;
    }
}

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
$goc    = '/ltweb-doan-nhom05/'; // THÊM DÒNG NÀY ĐỂ SỬA LỖI ĐƯỜNG DẪN
require_once __DIR__ . '/../../inc/header.php';
?>

<!-- Gọi riêng CSS cá nhân của Hải -->
<link rel="stylesheet" href="style.css">

<main class="container">
    
    <!-- CỘT TRÁI: Thông tin cá nhân & Kỹ năng -->
    <section class="profile grid-col">
        <h2>Lê Dương Hoàng Hải</h2>
        
        <!-- HIỂN THỊ ẢNH ĐẠI DIỆN -->
        <img src="images/<?= htmlspecialchars($avatarHienTai) ?>" alt="Ảnh chân dung của Lê Dương Hoàng Hải" width="150" height="150" style="object-fit: cover; border-radius: 5px;">
        
        <!-- FORM ĐỔI ẢNH ĐẠI DIỆN (CHỨC NĂNG 1) -->
        <form action="gioithieu.php" method="post" enctype="multipart/form-data" style="margin: 10px 0; padding: 10px; background: #f4f4f4; border-radius: 5px;">
            <label for="avatar" style="font-size: 0.9em; font-weight: bold;">Đổi ảnh đại diện (≤ 2MB):</label><br>
            <input type="file" name="avatar" id="avatar" accept="image/jpeg, image/png, image/webp" required style="font-size: 0.85em; margin: 5px 0;">
            <button type="submit" class="btn-primary" style="padding: 3px 10px; font-size: 0.85em;">Tải lên</button>
            
            <?php if ($thongBaoAnh !== ''): ?>
                <p style="color: green; font-size: 0.85em; margin-top: 5px;"><?= htmlspecialchars($thongBaoAnh) ?></p>
            <?php endif; ?>
            <?php if ($loiAnh !== ''): ?>
                <p style="color: red; font-size: 0.85em; margin-top: 5px;"><?= htmlspecialchars($loiAnh) ?></p>
            <?php endif; ?>
        </form>

        <!-- KHỐI ĐẾM NGƯỢC BẢO VỆ ĐỒ ÁN (CHỨC NĂNG 3) -->
        <div style="margin: 10px 0; padding: 10px 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; font-size: 0.88em; color: #856404;">
            ⏳ <strong>Đếm ngược bảo vệ Đồ án:</strong> 
            <?php if ($ngayHienTai < $ngayBaoVe): ?>
                Còn <strong><?= $thoiGianConLai->days ?></strong> ngày <strong><?= $thoiGianConLai->h ?></strong> giờ nữa!
            <?php else: ?>
                Đã đến ngày bảo vệ đồ án!
            <?php endif; ?>
        </div>

        <!-- KHỐI CHÂM NGÔN IT NGẪU NHIÊN (CHỨC NĂNG 2) -->
        <div style="margin: 10px 0 15px 0; padding: 10px 12px; background: #eef2ff; border-left: 4px solid #4f46e5; border-radius: 4px; font-style: italic; font-size: 0.88em; color: #3730a3;">
            💡 <strong>Châm ngôn hôm nay:</strong> "<?= htmlspecialchars($chamNgonNgauNhien) ?>"
        </div>

        <p>Xin chào! Mình là sinh viên ngành Công nghệ Thông tin tại Khoa Toán - Tin, Trường Đại học Sư phạm - Đại học Đà Nẵng. Mình đam mê phát triển phần mềm và ứng dụng thị giác máy tính.</p>
        
        <div class="like-section">
            <button id="btn-like" class="btn-primary" type="button">
                Ủng hộ Hải: ❤️ <span id="like-count">0</span>
            </button>
        </div>

        <div class="skills-box">
            <h3>Kỹ năng công nghệ:</h3>
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

<script type="module" src="js/canhan.js"></script>

<?php 
// 3. Nạp Footer dùng chung
require_once __DIR__ . '/../../inc/footer.php'; 
?>