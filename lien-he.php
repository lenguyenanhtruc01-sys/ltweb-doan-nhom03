<?php
// Nạp cấu hình chung và class xử lý dữ liệu
require_once __DIR__ . '/inc/config.php';
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
        $loi['sdt'] = 'Số điện thoại phải bắt đầu bằng số 0 và gồm đúng 10 chữ số.';
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
            // Dùng finfo kiểm tra định dạng
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $choPhep = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $choPhep, true)) {
                $loi['anh'] = 'Hệ thống chỉ chấp nhận ảnh JPG, PNG hoặc WebP.';
            } else {
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
        
        // Lưu thông báo vào Session
        $_SESSION['flash'] = 'Đã gửi liên hệ thành công. Đội ngũ EduGPA sẽ phản hồi qua email của bạn sớm nhất!';
        header('Location: lien-he.php'); // Chuyển hướng PRG
        exit;
    }
}

// Lấy thông báo flash và xóa ngay
$tb = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Thiết lập tiêu đề và nhúng Header
$tieuDe = 'Liên hệ';
$trang  = 'lien-he';
require_once __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <h1>Liên hệ với EduGPA</h1>
    <p>
        Nếu bạn có câu hỏi, góp ý hoặc gặp vấn đề khi sử dụng EduGPA, hãy liên hệ với đội ngũ hỗ trợ qua các kênh bên dưới. Chúng tôi luôn sẵn sàng lắng nghe và phản hồi trong thời gian sớm nhất.
    </p>

    <section class="lien-he__bieu-mau">
        <h2>Gửi câu hỏi cho chúng tôi</h2>
        <p>Điền biểu mẫu bên dưới để gửi câu hỏi hoặc yêu cầu hỗ trợ. Đội ngũ EduGPA sẽ phản hồi qua email trong vòng 24 giờ làm việc.</p>

        <?php if ($tb !== ''): ?>
            <p class="thong-bao-thanh-cong" style="color: #155724; font-weight: bold; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">
                <?= e($tb) ?>
            </p>
        <?php endif; ?>

        <form id="form-lien-he" class="form-lien-he" action="" method="post" enctype="multipart/form-data" novalidate>
            <fieldset>
                <legend>Thông tin người liên hệ</legend>
                <p>
                    <label for="hoten">Họ và tên <span aria-hidden="true">*</span></label>
                    <input type="text" id="hoten" name="hoten" value="<?= e($du['hoten']) ?>" minlength="3" maxlength="50" placeholder="Nguyễn Văn A" autocomplete="name" required>
                    <?php if (isset($loi['hoten'])): ?>
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['hoten']) ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <label for="email">Email liên hệ <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($du['email']) ?>" placeholder="ban@example.com" autocomplete="email" required>
                    <?php if (isset($loi['email'])): ?>
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['email']) ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <label for="sdt">Số điện thoại</label>
                    <input type="tel" id="sdt" name="sdt" value="<?= e($du['sdt']) ?>" placeholder="0901234567" autocomplete="tel">
                    <?php if (isset($loi['sdt'])): ?>
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['sdt']) ?></small>
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
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['chude']) ?></small>
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
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['noidung']) ?></small>
                    <?php endif; ?>
                </p>
                
                <p>
                    <label for="anh">Ảnh đính kèm minh họa lỗi (nếu có - tối đa 2MB)</label>
                    <input type="file" id="anh" name="anh" accept="image/jpeg, image/png, image/webp" style="margin-top: 5px;">
                    <?php if (isset($loi['anh'])): ?>
                        <small class="loi-bieu-mau" style="color: red; display:block; margin-top:5px;"><?= e($loi['anh']) ?></small>
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

    <section class="lien-he__thong-tin">
        <h2>Thông tin liên hệ trực tiếp</h2>
        <ul>
            <li><strong>Địa chỉ:</strong> Khoa Toán - Tin, Trường Đại học Sư phạm - Đại học Đà Nẵng</li>
            <li><strong>Hotline:</strong> <a href="tel:0901234567">0901 234 567</a></li>
            <li><strong>Email:</strong> <a href="mailto:support@edugpa.vn">support@edugpa.vn</a></li>
            <li><strong>Fanpage:</strong> <a href="https://facebook.com/edugpa" target="_blank" rel="noopener noreferrer">facebook.com/edugpa</a></li>
            <li><strong>Giờ làm việc:</strong> Thứ 2 - Thứ 6 (8:00 - 17:00), Thứ 7 (8:00 - 11:30)</li>
        </ul>
        <h3>Bản đồ</h3>
        <figure>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3834.0739377611776!2d108.15654371138382!3d16.061652539575185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31421924682e8689%3A0x48eb0bdbeec05215!2zVHLGsOG7nW5nIMSQ4bqhaSBI4buNYyBTxrAgUGjhuqFtIC0gxJDhuqFpIGjhu41jIMSQw6AgTuG6tW5n!5e0!3m2!1svi!2s!4v1789143046077!5m2!1svi!2s" width="600" height="400" title="Bản đồ vị trí Khoa Toán - Tin" loading="lazy" allowfullscreen></iframe>
            <figcaption>Vị trí Khoa Toán - Tin trên bản đồ.</figcaption>
        </figure>
    </section>

    <section class="lien-he__cau-hoi">
        <h2>Câu hỏi thường gặp</h2>
        <details><summary>Làm sao để tính GPA học kỳ trên EduGPA?</summary><p>Sau khi nhập điểm và số tín chỉ của từng môn trong học kỳ, EduGPA sẽ tự động tính GPA học kỳ dựa trên công thức trung bình có trọng số theo tín chỉ.</p></details>
        <details><summary>EduGPA có hỗ trợ mô phỏng GPA không?</summary><p>Có. Bạn có thể thử các mức điểm dự kiến như A, B+, B, C cho những môn chưa có điểm để xem GPA dự kiến thay đổi như thế nào.</p></details>
        <details><summary>Tôi quên mật khẩu thì phải làm sao?</summary><p>Hãy gửi yêu cầu hỗ trợ ở biểu mẫu phía trên với chủ đề “Vấn đề tài khoản”. Đội ngũ EduGPA sẽ phản hồi qua email trong vòng 24 giờ làm việc.</p></details>
        <details><summary>Dữ liệu điểm của tôi có được bảo mật không?</summary><p>Có. Dữ liệu điểm chỉ hiển thị cho chính tài khoản của bạn và quản trị viên hệ thống. EduGPA không chia sẻ dữ liệu cho bên thứ ba.</p></details>
        <details><summary>EduGPA có hỗ trợ trên điện thoại không?</summary><p>Có. Giao diện EduGPA được thiết kế responsive, có thể sử dụng trên máy tính, máy tính bảng và điện thoại thông minh.</p></details>
    </section>

    <aside class="kham-pha">
        <h2>Khám phá EduGPA</h2>
        <ul>
            <li><a href="index.php">Quay lại trang chủ EduGPA</a></li>
            <li><a href="danh-sach.php">Xem danh sách môn học</a></li>
            <li><a href="chi-tiet.php">Xem chi tiết kết quả học tập</a></li>
            <li><a href="gioi-thieu.php">Tìm hiểu về EduGPA và nhóm thực hiện</a></li>
        </ul>
    </aside>
</main>

<?php require_once __DIR__ . '/inc/footer.php'; ?>