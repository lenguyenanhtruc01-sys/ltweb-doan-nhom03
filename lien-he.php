<?php
// Nạp cấu hình chung và class xử lý dữ liệu
require __DIR__ . '/inc/config.php';
use App\Data\KhoLienHe;

// Khởi tạo mảng lưu dữ liệu người dùng nhập và mảng chứa lỗi
$du  = ['hoten' => '', 'email' => '', 'sdt' => '', 'chude' => '', 'uutien' => 'trungbinh', 'noidung' => ''];
$loi = [];
$tenAnh = null;

// Nếu người dùng bấm Gửi form (phương thức POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Nhận và làm sạch dữ liệu
    $du['hoten']   = trim($_POST['hoten'] ?? '');
    $du['email']   = trim($_POST['email'] ?? '');
    $du['sdt']     = trim($_POST['sdt'] ?? '');
    $du['chude']   = trim($_POST['chude'] ?? '');
    $du['uutien']  = trim($_POST['uutien'] ?? '');
    $du['noidung'] = trim($_POST['noidung'] ?? '');

    // 2. Kiểm tra lỗi (Validation Server)
    if (mb_strlen($du['hoten']) < 3 || mb_strlen($du['hoten']) > 50) {
        $loi['hoten'] = 'Họ tên bắt buộc phải từ 3 đến 50 ký tự.';
    }
    
    if (!filter_var($du['email'], FILTER_VALIDATE_EMAIL)) {
        $loi['email'] = 'Định dạng email không hợp lệ.';
    }
    
    if ($du['sdt'] !== '' && !preg_match('/^0[0-9]{9}$/', $du['sdt'])) {
        $loi['sdt'] = 'Số điện thoại phải bắt đầu bằng 0 và gồm đúng 10 chữ số.';
    }
    
    if (empty($du['chude'])) {
        $loi['chude'] = 'Vui lòng chọn chủ đề cần hỗ trợ.';
    }
    
    if (mb_strlen($du['noidung']) < 10 || mb_strlen($du['noidung']) > 1000) {
        $loi['noidung'] = 'Nội dung bắt buộc phải từ 10 đến 1000 ký tự.';
    }

    // 3. Xử lý Upload ảnh (nếu có đính kèm)
    if (isset($_FILES['anh']) && $_FILES['anh']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['anh'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $loi['anh'] = 'Có lỗi trong quá trình tải ảnh lên.';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // Giới hạn 2MB
            $loi['anh'] = 'Kích thước ảnh không được vượt quá 2MB.';
        } else {
            // Dùng finfo để kiểm tra định dạng thật của file (không tin tưởng đuôi file)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $choPhep = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $choPhep, true)) {
                $loi['anh'] = 'Hệ thống chỉ chấp nhận ảnh JPG, PNG hoặc WebP.';
            } else {
                // Tạo tên file ngẫu nhiên để tránh trùng lặp
                $phanMoRong = pathinfo($file['name'], PATHINFO_EXTENSION);
                $tenAnh     = uniqid('lh_', true) . '.' . $phanMoRong;
                $duongDan   = __DIR__ . '/uploads/' . $tenAnh;
                
                if (!move_uploaded_file($file['tmp_name'], $duongDan)) {
                    $loi['anh'] = 'Không thể lưu ảnh vào máy chủ, vui lòng thử lại.';
                    $tenAnh = null;
                }
            }
        }
    }

    // 4. Nếu không có lỗi nào -> Lưu dữ liệu và Chuyển hướng (PRG)
    if (empty($loi)) {
        $kho = new KhoLienHe(__DIR__ . '/storage/lien-he.jsonl');
        $kho->them([
            'thoiGian' => date('Y-m-d H:i:s'),
            'hoTen'    => $du['hoten'],
            'email'    => $du['email'],
            'sdt'      => $du['sdt'],
            'chuDe'    => $du['chude'],
            'uuTien'   => $du['uutien'],
            'noiDung'  => $du['noidung'],
            'anh'      => $tenAnh
        ]);
        
        // Lưu thông báo vào Session để hiện ra sau khi chuyển hướng
        $_SESSION['flash'] = 'Đã gửi liên hệ thành công. Cảm ơn bạn!';
        header('Location: lien-he.php'); // Chuyển hướng (Post/Redirect/Get)
        exit;
    }
}

// Lấy thông báo flash (nếu có) và xóa ngay khỏi session
$tb = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Thiết lập tiêu đề và nhúng Header
$tieuDe = 'Liên hệ';
$trang  = 'lien-he';
require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <h1>Liên hệ với EduGPA</h1>
    <p>
        Nếu bạn có câu hỏi, góp ý hoặc gặp vấn đề khi sử dụng EduGPA, hãy liên hệ với đội ngũ hỗ trợ qua các kênh bên dưới. Chúng tôi luôn sẵn sàng lắng nghe và phản hồi trong thời gian sớm nhất.
    </p>

    <section class="lien-he__bieu-mau">
        <h2>Gửi câu hỏi cho chúng tôi</h2>
        <p>Điền biểu mẫu bên dưới để gửi câu hỏi hoặc yêu cầu hỗ trợ. Đội ngũ EduGPA sẽ phản hồi qua email trong vòng 24 giờ làm việc.</p>

        <!-- Nếu có thông báo thành công từ Session -->
        <?php if ($tb !== ''): ?>
            <p class="thong-bao-thanh-cong" style="color: green; font-weight: bold; padding: 10px; background: #e6ffe6; border: 1px solid green; border-radius: 5px;">
                <?= e($tb) ?>
            </p>
        <?php endif; ?>

        <!-- Đổi action về rỗng, đổi method thành post và THÊM enctype để upload ảnh -->
        <form id="form-lien-he" class="form-lien-he" action="" method="post" enctype="multipart/form-data" novalidate>
            <fieldset>
                <legend>Thông tin người liên hệ</legend>
                <p>
                    <label for="hoten">Họ và tên <span aria-hidden="true">*</span></label>
                    <input type="text" id="hoten" name="hoten" value="<?= e($du['hoten']) ?>" minlength="3" maxlength="50" placeholder="Nguyễn Văn A" autocomplete="name" required>
                    <?php if (isset($loi['hoten'])): ?>
                        <small class="loi-bieu-mau" style="color: red;"><?= e($loi['hoten']) ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <label for="email">Email liên hệ <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($du['email']) ?>" placeholder="ban@example.com" autocomplete="email" required>
                    <?php if (isset($loi['email'])): ?>
                        <small class="loi-bieu-mau" style="color: red;"><?= e($loi['email']) ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <label for="sdt">Số điện thoại</label>
                    <input type="tel" id="sdt" name="sdt" value="<?= e($du['sdt']) ?>" placeholder="0901234567" autocomplete="tel">
                    <?php if (isset($loi['sdt'])): ?>
                        <small class="loi-bieu-mau" style="color: red;"><?= e($loi['sdt']) ?></small>
                    <?php endif; ?>
                </p>
            </fieldset>

            <fieldset>
                <legend>Nội dung liên hệ</legend>
                <p>
                    <label for="chude">Chủ đề cần hỗ trợ <span aria-hidden="true">*</span></label>
                    <select id="chude" name="chude" required>
                        <option value="">-- Chọn chủ đề --</option>
                        <option value="gpa" <?= $du['chude'] === 'gpa' ? 'selected' : '' ?>>Lỗi tính GPA</option>
                        <option value="diem" <?= $du['chude'] === 'diem' ? 'selected' : '' ?>>Lỗi nhập điểm</option>
                        <option value="taikhoan" <?= $du['chude'] === 'taikhoan' ? 'selected' : '' ?>>Vấn đề tài khoản</option>
                        <option value="tailieu" <?= $du['chude'] === 'tailieu' ? 'selected' : '' ?>>Tài liệu học tập</option>
                        <option value="gopy" <?= $du['chude'] === 'gopy' ? 'selected' : '' ?>>Góp ý cải thiện hệ thống</option>
                        <option value="khac" <?= $du['chude'] === 'khac' ? 'selected' : '' ?>>Khác</option>
                    </select>
                    <?php if (isset($loi['chude'])): ?>
                        <small class="loi-bieu-mau" style="color: red;"><?= e($loi['chude']) ?></small>
                    <?php endif; ?>
                </p>

                <p><strong>Mức độ ưu tiên</strong></p>
                <p>
                    <input type="radio" id="thap" name="uutien" value="thap" <?= $du['uutien'] === 'thap' ? 'checked' : '' ?>>
                    <label for="thap">Thấp</label>

                    <input type="radio" id="trungbinh" name="uutien" value="trungbinh" <?= $du['uutien'] === 'trungbinh' ? 'checked' : '' ?>>
                    <label for="trungbinh">Trung bình</label>

                    <input type="radio" id="cao" name="uutien" value="cao" <?= $du['uutien'] === 'cao' ? 'checked' : '' ?>>
                    <label for="cao">Cao</label>
                </p>

                <p>
                    <label for="noidung">Nội dung câu hỏi <span aria-hidden="true">*</span></label>
                    <textarea id="noidung" name="noidung" rows="5" cols="40" minlength="10" maxlength="1000" placeholder="Mô tả chi tiết câu hỏi hoặc vấn đề bạn gặp phải..." required><?= e($du['noidung']) ?></textarea>
                    <?php if (isset($loi['noidung'])): ?>
                        <small class="loi-bieu-mau" style="color: red;"><?= e($loi['noidung']) ?></small>
                    <?php endif; ?>
                </p>
                
                <!-- TRƯỜNG ĐÍNH KÈM ẢNH ĐƯỢC THÊM VÀO -->
                <p>
                    <label for="anh">Ảnh đính kèm minh họa lỗi (nếu có - tối đa 2MB)</label>
                    <input type="file" id="anh" name="anh" accept="image/jpeg, image/png, image/webp">
                    <?php if (isset($loi['anh'])): ?>
                        <br><small class="loi-bieu-mau" style="color: red;"><?= e($loi['anh']) ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <input type="checkbox" id="phanhoi" name="phanhoi" checked>
                    <label for="phanhoi">Tôi muốn nhận phản hồi qua email</label>
                </p>
            </fieldset>

            <p class="form-lien-he__nhom-nut">
                <button id="nut-gui" type="submit">Gửi liên hệ</button>
                <button id="nut-nhap-lai" type="reset">Nhập lại</button>
            </p>
        </form>
    </section>

    <!-- Các section thông tin liên hệ và câu hỏi thường gặp giữ nguyên từ file HTML gốc... -->
    <!-- Do giới hạn độ dài, bạn giữ nguyên phần HTML từ <section class="lien-he__thong-tin"> đến hết thẻ </aside> của bạn nhé -->
    
</main>

<?php 
// Nhúng Footer chung
require __DIR__ . '/inc/footer.php'; 
?>