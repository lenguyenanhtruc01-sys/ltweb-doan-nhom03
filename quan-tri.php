<?php
// Bắt buộc phải nhúng file bảo vệ ở dòng đầu tiên
require_once __DIR__ . '/inc/bao-ve.php';
require_once __DIR__ . '/inc/config.php';

// Đọc danh sách liên hệ từ file JSON (giả sử lưu tại data/lien-he.json)
$fileLienHe = __DIR__ . '/data/lien-he.json';
$danhSachLienHe = [];

if (file_exists($fileLienHe)) {
    $noiDungJson = file_get_contents($fileLienHe);
    $danhSachLienHe = json_decode($noiDungJson, true);
    if (!is_array($danhSachLienHe)) {
        $danhSachLienHe = [];
    }
}

// Sắp xếp mới nhất lên trước (dựa vào thời gian hoặc ID giảm dần)
usort($danhSachLienHe, function($a, $b) {
    $timeA = $a['thoi_gian'] ?? '';
    $timeB = $b['thoi_gian'] ?? '';
    return strcmp($timeB, $timeA); // Đảo ngược thời gian để mới nhất lên đầu
});

require __DIR__ . '/inc/header.php';
?>

    <main class="noi-dung-chinh">
        <div class="bao">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>Trang quản trị hệ thống EduGPA</h1>
                <div>
                    <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['ho_ten'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <a href="dang-xuat.php" class="nut" style="margin-left: 1rem; background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">Đăng xuất</a>
                </div>
            </div>

            <h2>Danh sách các liên hệ đã nhận</h2>
            <p>Các thông tin liên hệ gửi từ người dùng được hiển thị theo thứ tự mới nhất lên trước.</p>

            <div class="bang-cuon" style="margin-top: 1.5rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #e9ecef; text-align: left;">
                            <th style="padding: 10px; border: 1px solid #dee2e6;">Thời gian</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">Họ tên</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">Email / SĐT</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">Nội dung</th>
                            <th style="padding: 10px; border: 1px solid #dee2e6;">Hình ảnh đính kèm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSachLienHe)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">Chưa có liên hệ nào được gửi tới hệ thống.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSachLienHe as $lh): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; white-space: nowrap;"><?= htmlspecialchars($lh['thoi_gian'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;"><?= htmlspecialchars($lh['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><?= htmlspecialchars($lh['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($lh['noi_dung'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">
                                        <?php if (!empty($lh['hinh_anh'])): ?>
                                            <img src="<?= htmlspecialchars($lh['hinh_anh'], ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh đính kèm" style="max-width: 80px; height: auto; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-style: italic;">Không có ảnh</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php
require __DIR__ . '/inc/footer.php';
?>