<?php
// 1. Nạp cấu hình chung từ thư mục gốc (lùi 2 cấp thư mục)
require_once __DIR__ . '/../../inc/config.php';

// ==========================================
// CHỨC NĂNG 1: XỬ LÝ UPLOAD ẢNH ĐẠI DIỆN
// ==========================================
$thongBaoAnh = $_SESSION['flash_avatar'] ?? '';
$loiAnh      = $_SESSION['loi_avatar'] ?? '';
unset($_SESSION['flash_avatar'], $_SESSION['loi_avatar']); // Lấy xong xóa ngay (PRG)

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
        // Kiểm tra định dạng bằng finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $choPhep = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $choPhep, true)) {
            $_SESSION['loi_avatar'] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $tenMoi = 'avatar_3120224042.' . $ext; // Đổi tên file ngẫu nhiên/cố định theo MSSV
            
            if (move_uploaded_file($file['tmp_name'], $thuMucAnh . $tenMoi)) {
                $_SESSION['flash_avatar'] = 'Cập nhật ảnh đại diện thành công!';
            } else {
                $_SESSION['loi_avatar'] = 'Không thể lưu ảnh vào máy chủ.';
            }
        }
    }
    // Chuyển hướng để tránh người dùng F5 gửi lại Form (PRG)
    header('Location: gioithieu.php');
    exit;
}

// Tìm file avatar hiện tại (nếu đã upload thì dùng, chưa thì dùng mặc định)
$avatarHienTai = 'hoanghai.jpg'; 
$cacDuoi = ['jpg', 'png', 'webp', 'jpeg'];
foreach ($cacDuoi as $d) {
    if (file_exists(__DIR__ . '/images/avatar_3120224042.' . $d)) {
        $avatarHienTai = 'avatar_3120224042.' . $d;
        break;
    }
}

// ==========================================
// CHỨC NĂNG 2: LỌC DANH SÁCH KỸ NĂNG BẰNG GET
// ==========================================
$danhSachKyNang = [
    ['loai' => 'ngon-ngu', 'nhom' => 'Ngôn ngữ', 'chitiet' => 'C++, Python, SQL.'],
    ['loai' => 'mobile',   'nhom' => 'Mobile',   'chitiet' => 'Jetpack Compose (Kotlin).'],
    ['loai' => 'cv',       'nhom' => 'Computer Vision', 'chitiet' => 'OpenCV, YOLOv8, YOLO-NAS.'],
    ['loai' => 'cong-cu',  'nhom' => 'Công cụ',  'chitiet' => 'VS Code, SSMS, Git.']
];

// Lấy tham số loại từ URL (GET)
$loaiLoc = $_GET['loai'] ?? 'tat-ca';

// Lọc mảng dữ liệu dựa trên tham số GET
$duLieuLoc = array_filter($danhSachKyNang, function($kn) use ($loaiLoc) {
    return $loaiLoc === 'tat-ca' || $kn['loai'] === $loaiLoc;
});


// 3. Nạp Tiêu đề & Header dùng chung
$tieuDe = 'Lê Dương Hoàng Hải | Giới thiệu cá nhân';
$trang  = 'gioi-thieu';
require_once __DIR__ . '/../../inc/header.php';
?>

<!-- Gọi riêng CSS cá nhân của Hải để không ảnh hưởng trang khác -->
<link rel="stylesheet" href="style.css">

<main class="container">
    
    <!-- CỘT TRÁI: Thông tin cá nhân & Kỹ năng -->
    <section class="profile grid-col">
        <h2>Lê Dương Hoàng Hải</h2>
        
        <!-- HIỂN THỊ VÀ UPLOAD ẢNH ĐẠI DIỆN -->
        <img src="images/<?= e($avatarHienTai) ?>" alt="Ảnh chân dung của Lê Dương Hoàng Hải" width="150" height="150" style="object-fit: cover; border-radius: 5px;">
        
        <!-- Form đổi ảnh cá nhân -->
        <form action="gioithieu.php" method="post" enctype="multipart/form-data" style="margin: 10px 0; padding: 10px; background: #f4f4f4; border-radius: 5px;">
            <label for="avatar" style="font-size: 0.9em; font-weight: bold;">Đổi ảnh đại diện (≤ 2MB):</label><br>
            <input type="file" name="avatar" id="avatar" accept="image/jpeg, image/png, image/webp" required style="font-size: 0.85em; margin: 5px 0;">
            <button type="submit" class="btn-primary" style="padding: 3px 10px; font-size: 0.85em;">Tải lên</button>
            
            <!-- In thông báo upload -->
            <?php if ($thongBaoAnh !== ''): ?>
                <p style="color: green; font-size: 0.85em; margin-top: 5px;"><?= e($thongBaoAnh) ?></p>
            <?php endif; ?>
            <?php if ($loiAnh !== ''): ?>
                <p style="color: red; font-size: 0.85em; margin-top: 5px;"><?= e($loiAnh) ?></p>
            <?php endif; ?>
        </form>

        <p>Xin chào! Mình là sinh viên ngành Công nghệ Thông tin tại Khoa Toán - Tin, Trường Đại học Sư phạm - Đại học Đà Nẵng. Mình đam mê phát triển phần mềm và ứng dụng thị giác máy tính.</p>
        
        <div class="like-section">
            <button id="btn-like" class="btn-primary" type="button">
                Ủng hộ Hải: ❤️ <span id="like-count">0</span>
            </button>
        </div>

        <!-- LỌC DANH SÁCH KỸ NĂNG BẰNG GET -->
        <div class="skills-box">
            <h3>Kỹ năng công nghệ:</h3>
            
            <!-- Nút bấm chuyển hướng truyền tham số GET trên URL -->
            <div style="margin-bottom: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                <a href="gioithieu.php?loai=tat-ca"   class="<?= $loaiLoc === 'tat-ca' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 2px 8px; text-decoration: none; font-size: 0.85em;">Tất cả</a>
                <a href="gioithieu.php?loai=ngon-ngu" class="<?= $loaiLoc === 'ngon-ngu' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 2px 8px; text-decoration: none; font-size: 0.85em;">Ngôn ngữ</a>
                <a href="gioithieu.php?loai=mobile"   class="<?= $loaiLoc === 'mobile' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 2px 8px; text-decoration: none; font-size: 0.85em;">Mobile</a>
                <a href="gioithieu.php?loai=cv"       class="<?= $loaiLoc === 'cv' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 2px 8px; text-decoration: none; font-size: 0.85em;">AI / CV</a>
                <a href="gioithieu.php?loai=cong-cu"  class="<?= $loaiLoc === 'cong-cu' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 2px 8px; text-decoration: none; font-size: 0.85em;">Công cụ</a>
            </div>

            <!-- In danh sách kỹ năng đã được lọc bằng PHP -->
            <ul>
                <?php if (empty($duLieuLoc)): ?>
                    <li>Không tìm thấy kỹ năng nào.</li>
                <?php else: ?>
                    <?php foreach ($duLieuLoc as $kn): ?>
                        <li><strong><?= e($kn['nhom']) ?>:</strong> <?= e($kn['chitiet']) ?></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </section>

    <!-- CỘT PHẢI: Sở thích & Thời khóa biểu (GIỮ NGUYÊN HTML CỦA BẠN) -->
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
                            <td rowspan="2"><strong>An toàn thông tin</strong>Phòng: B3-206</td>
                            <td></td><td></td>
                            <td rowspan="3"><strong>Khai phá dữ liệu</strong>Phòng: A5-404B</td>
                            <td rowspan="3"><strong>Lập trình mạng</strong>Phòng: B3-303</td>
                            <td rowspan="3"><strong>Thiết kế và lập trình web</strong>Phòng: B3-303</td>
                            <td></td>
                        </tr>
                        <!-- Tiết 2 -->
                        <tr>
                            <td class="tiet">2</td><td></td>
                            <td rowspan="3"><strong>Hệ quản trị cơ sở dữ liệu</strong>Phòng: B3-402</td><td></td>
                        </tr>
                        <!-- Tiết 3 -->
                        <tr>
                            <td class="tiet">3</td>
                            <td rowspan="2"><strong>Lịch sử Đảng Cộng sản Việt Nam</strong>Phòng: A5-404C</td>
                            <td></td><td></td>
                        </tr>
                        <!-- Tiết 4 -->
                        <tr>
                            <td class="tiet">4</td><td></td>
                            <td rowspan="2"><strong>Hệ phân tán</strong>Phòng: B3-303</td><td></td><td></td><td></td>
                        </tr>
                        <!-- Tiết 5 -->
                        <tr><td class="tiet">5</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 6 -->
                        <tr><td class="tiet">6</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <!-- Tiết 7 -->
                        <tr>
                            <td class="tiet">7</td><td></td><td></td><td></td>
                            <td rowspan="3"><strong>Công nghệ phần mềm</strong>Phòng: B3-303</td><td></td><td></td><td></td>
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

<!-- Nạp JS của riêng Hải (Vì footer sẽ đóng thẻ body nên để trước footer) -->
<script type="module" src="js/canhan.js"></script>

<?php 
// 4. Nạp Footer dùng chung
require_once __DIR__ . '/../../inc/footer.php'; 
?>