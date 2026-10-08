<?php
// gio-hang.php — Kế hoạch học tập (quản lý qua lớp dịch vụ OOP)
require_once __DIR__ . '/inc/config.php';

// Khởi tạo dịch vụ kế hoạch học tập (đã bọc session)
$keHoachService = new \App\Services\KeHoachHocTap();

// ===== Xử lý form (thêm / cập nhật / xóa) — trước khi in HTML =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanh_dong'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $soLuong = filter_var($_POST['so_luong'] ?? 1, FILTER_VALIDATE_INT);

    if ($hanhDong === 'them') {
        // Lấy danh sách hiện tại để kiểm tra
        $dsHienTai = $keHoachService->layDanhSachChiTiet()['danh_sach'] ?? [];
        
        $daTonTai = false;
        foreach ($dsHienTai as $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $daTonTai = true;
                break;
            }
        }
        
        if ($daTonTai) {
            // Chặn ghi đè (NO EDIT): Báo lỗi qua session nếu đã có trong giỏ
            $_SESSION['flash_error'] = 'Môn học này đã có trong kế hoạch của bạn!';
        } else {
            // Thêm mới nếu chưa có
            $keHoachService->them($id, $soLuong);
            $_SESSION['flash_success'] = 'Đã thêm vào kế hoạch học tập.';
        }
    } elseif ($hanhDong === 'cap_nhat') {
        $keHoachService->capNhat($id, $soLuong);
    } elseif ($hanhDong === 'xoa') {
        $keHoachService->xoa($id);
    } elseif ($hanhDong === 'xoa_het') {
        $keHoachService->xoaTatCa();
    }

    // PRG: tránh F5 gửi lại form
    header('Location: gio-hang.php');
    exit;
}

// Lấy danh sách chi tiết và tổng hợp từ service OOP
$duLieuGio = $keHoachService->layDanhSachChiTiet();
$danhSachMon = $duLieuGio['danh_sach'];
$soMon = $duLieuGio['tong_so_mon'];
$tongTinChi = $duLieuGio['tong_so_tin_chi'];

require __DIR__ . '/inc/header.php';
?>

<!-- Khai báo file CSS riêng đúng vị trí -->
<link rel="stylesheet" href="css/gio-hang.css">

<main class="noi-dung-chinh">
    <h1>Kế hoạch học tập</h1>

    <!-- Hiển thị thông báo khi thêm trùng môn -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <p style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?= e($_SESSION['flash_error']) ?>
        </p>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <p style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?= e($_SESSION['flash_success']) ?>
        </p>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (empty($danhSachMon)): ?>
        <div class="empty-cart">
            <p>Chưa có môn nào trong kế hoạch học tập của bạn.</p>
            <p style="margin-top: 10px;"><a href="danh-sach.php" class="btn" style="background:#007bff; color:#fff; display:inline-block; padding: 8px 16px;">Vào danh sách môn học để thêm</a></p>
        </div>
    <?php else: ?>
        <div class="cart-summary">
            Tổng số môn đăng ký: <strong><?= (int) $soMon ?></strong> — Tổng số tín chỉ tích lũy: <strong><?= (int) $tongTinChi ?></strong>
        </div>

        <table class="table-cart">
            <thead>
                <tr>
                    <th>Mã môn</th>
                    <th>Tên môn học</th>
                    <th>Tín chỉ</th>
                    <th>Số lượng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($danhSachMon as $item): ?>
                    <tr>
                        <td><code><?= e($item['maMon'] ?? $item['ma_mon'] ?? '') ?></code></td>
                        <td><strong><?= e($item['tenMon'] ?? $item['ten_mon'] ?? '') ?></strong></td>
                        <td><?= (int) ($item['soTinChi'] ?? $item['so_tin_chi'] ?? 0) ?></td>
                        <td>
                            <form action="gio-hang.php" method="post" style="display:inline-flex; gap:6px; align-items:center;">
                                <input type="hidden" name="hanh_dong" value="cap_nhat">
                                <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <input type="number" name="so_luong" value="<?= (int) ($item['soLuong'] ?? $item['so_luong'] ?? 1) ?>" min="1" max="5" class="input-number">
                                <button type="submit" class="btn btn-update">Cập nhật</button>
                            </form>
                        </td>
                        <td>
                            <form action="gio-hang.php" method="post" style="display:inline;">
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <button type="submit" class="btn btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; text-align: right;">
            <form action="gio-hang.php" method="post" style="display:inline;">
                <input type="hidden" name="hanh_dong" value="xoa_het">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ kế hoạch học tập không?');">Xóa hết kế hoạch</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/inc/footer.php';
?>git commit -m "Fix no edit/no update cho gio hang"