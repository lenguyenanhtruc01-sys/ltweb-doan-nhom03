<?php
// gio-hang.php — Kế hoạch học tập (tương đương giỏ hàng)
require __DIR__ . '/inc/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo session kế hoạch
if (!isset($_SESSION['gio']) || !is_array($_SESSION['gio'])) {
    $_SESSION['gio'] = [];
}

// ===== Xử lý form (thêm / cập nhật / xóa) — trước khi in HTML =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanh_dong'] ?? '';

    // Thêm môn
    if ($hanhDong === 'them') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $soLuong = filter_var(
            $_POST['so_luong'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 10]]
        );

        if ($id !== false && $id > 0 && $soLuong !== false) {
            if (isset($_SESSION['gio'][$id])) {
                $_SESSION['gio'][$id] += $soLuong;
            } else {
                $_SESSION['gio'][$id] = $soLuong;
            }
        }
    }

    // Đổi số lượng
    if ($hanhDong === 'cap_nhat') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $soLuong = filter_var(
            $_POST['so_luong'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 10]]
        );
        if ($id !== false && $id > 0 && $soLuong !== false && isset($_SESSION['gio'][$id])) {
            $_SESSION['gio'][$id] = $soLuong;
        }
    }

    // Xóa một môn
    if ($hanhDong === 'xoa') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && isset($_SESSION['gio'][$id])) {
            unset($_SESSION['gio'][$id]);
        }
    }

    // Xóa hết
    if ($hanhDong === 'xoa_het') {
        $_SESSION['gio'] = [];
    }

    // PRG: tránh F5 gửi lại form
    header('Location: gio-hang.php');
    exit;
}

// ===== Dữ liệu môn (tạm — sau này đọc từ JSON/class) =====
$dsMon = [
    1 => ['ten' => 'Thiết kế và Lập trình Web', 'ma' => 'INT101', 'tin_chi' => 3],
    2 => ['ten' => 'Hệ quản trị cơ sở dữ liệu', 'ma' => 'INT102', 'tin_chi' => 3],
    3 => ['ten' => 'Khai phá dữ liệu',         'ma' => 'INT103', 'tin_chi' => 3],
    4 => ['ten' => 'Công nghệ phần mềm',       'ma' => 'INT104', 'tin_chi' => 3],
    5 => ['ten' => 'An toàn thông tin',        'ma' => 'INT105', 'tin_chi' => 3],
];

$tongTinChi = 0;
$soMon = 0;
foreach ($_SESSION['gio'] as $id => $sl) {
    if (isset($dsMon[$id])) {
        $tongTinChi += $dsMon[$id]['tin_chi'] * $sl;
        $soMon += $sl;
    }
}

require __DIR__ . '/inc/header.php';
?>

<!-- CSS Tối ưu giao diện trang Kế hoạch học tập -->
<style>
    .noi-dung-chinh {
        max-width: 900px;
        margin: 30px auto;
        padding: 25px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        font-family: Arial, sans-serif;
    }
    .noi-dung-chinh h1 {
        margin-bottom: 20px;
        color: #333;
        font-size: 24px;
        border-bottom: 2px solid #eaeaea;
        padding-bottom: 10px;
    }
    .cart-summary {
        background: #f8f9fa;
        padding: 12px 18px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 15px;
        color: #555;
        border-left: 4px solid #007bff;
    }
    .table-cart {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .table-cart th, .table-cart td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    .table-cart th {
        background-color: #f1f3f5;
        color: #333;
        font-weight: bold;
    }
    .table-cart tr:hover {
        background-color: #fafbfc;
    }
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: background 0.2s;
    }
    .btn-update {
        background-color: #e9ecef;
        color: #333;
        border: 1px solid #ced4da;
    }
    .btn-update:hover {
        background-color: #dde2e6;
    }
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }
    .input-number {
        width: 50px;
        padding: 5px;
        text-align: center;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .empty-cart {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    .empty-cart a {
        color: #007bff;
        text-decoration: none;
    }
    .empty-cart a:hover {
        text-decoration: underline;
    }
</style>

<main class="noi-dung-chinh">
    <h1>Kế hoạch học tập</h1>

    <?php if (empty($_SESSION['gio'])): ?>
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
                <?php foreach ($_SESSION['gio'] as $id => $sl): ?>
                    <?php if (!isset($dsMon[$id])) { continue; } ?>
                    <tr>
                        <td><code><?= htmlspecialchars($dsMon[$id]['ma'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><strong><?= htmlspecialchars($dsMon[$id]['ten'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= (int) $dsMon[$id]['tin_chi'] ?></td>
                        <td>
                            <form action="gio-hang.php" method="post" style="display:inline-flex; gap:6px; align-items:center;">
                                <input type="hidden" name="hanh_dong" value="cap_nhat">
                                <input type="hidden" name="id" value="<?= (int) $id ?>">
                                <input type="number" name="so_luong" value="<?= (int) $sl ?>" min="1" max="10" class="input-number">
                                <button type="submit" class="btn btn-update">Cập nhật</button>
                            </form>
                        </td>
                        <td>
                            <form action="gio-hang.php" method="post" style="display:inline;">
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int) $id ?>">
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
                <button type="submit" class="btn btn-danger" style="background-color: #6c757d;" onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ kế hoạch học tập không?');">Xóa hết kế hoạch</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/inc/footer.php';
?>