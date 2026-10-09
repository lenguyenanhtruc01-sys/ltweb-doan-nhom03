<?php
require __DIR__ . '/inc/config.php';

// ==========================================
// 1. CLASS XỬ LÝ DỮ LIỆU (Mô phỏng Database)
// ==========================================
class KhoMonHoc {
    private $duLieu = [
        ['id' => 1, 'ma_mon' => 'INT101', 'ten_mon' => 'Thiết kế và Lập trình Web', 'tin_chi' => 3, 'trang_thai' => 'Đang học', 'hinh_anh' => 'images/int101.svg'],
        ['id' => 2, 'ma_mon' => 'INT102', 'ten_mon' => 'Hệ quản trị cơ sở dữ liệu', 'tin_chi' => 3, 'trang_thai' => 'Đang học', 'hinh_anh' => 'images/int102.svg'],
        ['id' => 3, 'ma_mon' => 'INT103', 'ten_mon' => 'Khai phá dữ liệu', 'tin_chi' => 3, 'trang_thai' => 'Đang học', 'hinh_anh' => 'images/int103.svg'],
        ['id' => 4, 'ma_mon' => 'INT104', 'ten_mon' => 'Công nghệ phần mềm', 'tin_chi' => 3, 'trang_thai' => 'Đang học', 'hinh_anh' => 'images/int104.svg'],
        ['id' => 5, 'ma_mon' => 'INT105', 'ten_mon' => 'An toàn thông tin', 'tin_chi' => 3, 'trang_thai' => 'Đăng ký mới', 'hinh_anh' => 'images/int105.svg'],
    ];

    public function layDanhSach() {
        return $this->duLieu;
    }
}

// Khởi tạo Class và lấy dữ liệu
$kho = new KhoMonHoc();
$danhSach = $kho->layDanhSach();

// ==========================================
// 2. LẤY THAM SỐ TỪ METHOD GET (Tìm kiếm & Lọc)
// ==========================================
$tuKhoa    = trim($_GET['tu-khoa'] ?? '');
$trangThai = $_GET['trang-thai'] ?? 'tat-ca';
$sapXep    = $_GET['sap-xep'] ?? 'mac-dinh';

// ==========================================
// 3. XỬ LÝ TÌM KIẾM VÀ LỌC (Filtering)
// ==========================================
$danhSach = array_filter($danhSach, function($mon) use ($tuKhoa, $trangThai) {
    // Lọc theo trạng thái
    $matchTrangThai = true;
    if ($trangThai !== 'tat-ca') {
        $matchTrangThai = ($mon['trang_thai'] === $trangThai);
    }

    // Tìm kiếm theo tên hoặc mã môn
    $matchTuKhoa = true;
    if ($tuKhoa !== '') {
        $tuKhoaLower = mb_strtolower($tuKhoa, 'UTF-8');
        $tenMonLower = mb_strtolower($mon['ten_mon'], 'UTF-8');
        $maMonLower  = mb_strtolower($mon['ma_mon'], 'UTF-8');
        
        $matchTuKhoa = (mb_strpos($tenMonLower, $tuKhoaLower) !== false) || 
                       (mb_strpos($maMonLower, $tuKhoaLower) !== false);
    }

    return $matchTrangThai && $matchTuKhoa;
});

// ==========================================
// 4. XỬ LÝ SẮP XẾP (Sorting)
// ==========================================
usort($danhSach, function($a, $b) use ($sapXep) {
    if ($sapXep === 'ten-az') {
        return strcmp($a['ten_mon'], $b['ten_mon']);
    } elseif ($sapXep === 'tin-chi-giam') {
        return $b['tin_chi'] <=> $a['tin_chi'];
    } elseif ($sapXep === 'tin-chi-tang') {
        return $a['tin_chi'] <=> $b['tin_chi'];
    }
    return $a['id'] <=> $b['id'];
});

// Nhúng Header
require __DIR__ . '/inc/header.php';
?>

<style>
/* ===== CSS Tổng thể & Biến màu sắc ===== */
:root {
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --accent: #0f766e;
    --accent-hover: #0d9488;
    --bg-main: #f8fafc;
    --card-bg: #ffffff;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --radius: 10px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
}
body { background-color: var(--bg-main); color: var(--text-main); }
.noi-dung-chinh { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.noi-dung-chinh > h1 { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 25px; border-bottom: 2px solid var(--border-color); padding-bottom: 12px; }
.section-bo-loc { background: var(--card-bg); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 35px; }
.section-bo-loc h2 { font-size: 18px; margin-bottom: 15px; color: #334155; }
.section-bo-loc p { color: var(--text-muted); font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
.form-tim-kiem { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; }
@media (max-width: 900px) { .form-tim-kiem { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .form-tim-kiem { grid-template-columns: 1fr; } }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #475569; }
.form-control { padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; background: #fff; color: var(--text-main); transition: all 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
.btn-loc { padding: 10px 24px; background: var(--primary); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; height: 42px; }
.btn-loc:hover { background: var(--primary-hover); }
.tieu-de-phan { font-size: 20px; font-weight: 600; margin-bottom: 20px; color: #334155; }
.luoi-san-pham { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.the-san-pham { background: var(--card-bg); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; transition: transform 0.25s ease, box-shadow 0.25s ease; position: relative; }
.the-san-pham:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.the-san-pham img { width: 100%; height: 180px; object-fit: cover; background: #f1f5f9; border-bottom: 1px solid var(--border-color); }
.the-san-pham__than { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
.the-san-pham__than h3 { font-size: 17px; font-weight: 600; color: #0f172a; margin-bottom: 10px; line-height: 1.4; }
.the-san-pham__than p { font-size: 14px; color: var(--text-muted); margin-bottom: 6px; }
.the-san-pham__than p strong { color: #334155; }
.the-san-pham__hanh-dong { display: flex; flex-direction: column; gap: 10px; margin-top: auto; padding-top: 15px; border-top: 1px solid #f1f5f9; }
.the-san-pham__o-xanh { display: flex; align-items: center; justify-content: space-between; background: #eff6ff; padding: 8px 12px; border-radius: 6px; }
.the-san-pham__lien-ket { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 13px; }
.the-san-pham__lien-ket:hover { text-decoration: underline; }
.nut-yeu-thich { background: #ffffff; border: 1px solid #cbd5e1; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.2s; }
.nut-yeu-thich:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
.nut-yeu-thich--da-chon { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
.nut-them-ke-hoach { width: 100%; padding: 10px; border: none; border-radius: 6px; background: var(--accent); color: #fff; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; text-align: center; }
.nut-them-ke-hoach:hover { background: var(--accent-hover); }
.visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>

<main class="noi-dung-chinh">
    <h1>Danh sách môn học</h1>

    <section class="section-bo-loc">
        <h2>Tra cứu và lọc môn học</h2>
        <p>Dưới đây là danh sách các môn học trong học kỳ 1, năm học 2026–2027. Sinh viên có thể tra cứu nhanh thông tin, lọc theo trạng thái hoặc sắp xếp theo nhu cầu học tập.</p>

        <form class="form-tim-kiem" action="danh-sach.php" method="get">
            <div class="form-group">
                <label for="tim-kiem">Tìm kiếm môn học</label>
                <input type="search" id="tim-kiem" name="tu-khoa" class="form-control" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập tên hoặc mã môn...">
            </div>

            <div class="form-group">
                <label for="loc-trang-thai">Trạng thái</label>
                <select id="loc-trang-thai" name="trang-thai" class="form-control">
                    <option value="tat-ca" <?= $trangThai === 'tat-ca' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="Đang học" <?= $trangThai === 'Đang học' ? 'selected' : '' ?>>Đang học</option>
                    <option value="Đã hoàn thành" <?= $trangThai === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                    <option value="Đăng ký mới" <?= $trangThai === 'Đăng ký mới' ? 'selected' : '' ?>>Đăng ký mới</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sap-xep">Sắp xếp</label>
                <select id="sap-xep" name="sap-xep" class="form-control">
                    <option value="mac-dinh" <?= $sapXep === 'mac-dinh' ? 'selected' : '' ?>>Mặc định (ID)</option>
                    <option value="ten-az" <?= $sapXep === 'ten-az' ? 'selected' : '' ?>>Tên môn học (A–Z)</option>
                    <option value="tin-chi-giam" <?= $sapXep === 'tin-chi-giam' ? 'selected' : '' ?>>Tín chỉ (Giảm dần)</option>
                    <option value="tin-chi-tang" <?= $sapXep === 'tin-chi-tang' ? 'selected' : '' ?>>Tín chỉ (Tăng dần)</option>
                </select>
            </div>

            <button type="submit" class="btn-loc">Lọc kết quả</button>
        </form>
    </section>

    <section>
        <h2 class="tieu-de-phan">Tiến độ các môn học (<?= count($danhSach) ?> kết quả)</h2>

        <div class="luoi-san-pham">
            <?php if (empty($danhSach)): ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted); background: var(--card-bg); border-radius: var(--radius); border: 1px dashed var(--border-color);">
                    Rất tiếc, không tìm thấy môn học nào khớp với tiêu chí tìm kiếm của bạn.
                </p>
            <?php else: ?>
                <?php foreach ($danhSach as $mon): ?>
                    <article class="the-san-pham">
                        <img src="<?= htmlspecialchars($mon['hinh_anh'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($mon['ten_mon'], ENT_QUOTES, 'UTF-8') ?>" width="400" height="250" loading="lazy">

                        <div class="the-san-pham__than">
                            <h3><?= htmlspecialchars($mon['ten_mon'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><strong>Mã môn:</strong> <?= htmlspecialchars($mon['ma_mon'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Số tín chỉ:</strong> <?= $mon['tin_chi'] ?></p>
                            <p>
                                <strong>Trạng thái:</strong> 
                                <span style="color: <?= $mon['trang_thai'] === 'Đang học' ? 'blue' : ($mon['trang_thai'] === 'Đăng ký mới' ? 'green' : 'gray') ?>; font-weight: 500;">
                                    <?= htmlspecialchars($mon['trang_thai'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </p>

                            <div class="the-san-pham__hanh-dong">
                                <div class="the-san-pham__o-xanh">
                                    <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=<?= $mon['id'] ?>">Xem chi tiết &rarr;</a>
                                    <button type="button" class="nut-yeu-thich" data-yeu-thich-id="<?= $mon['id'] ?>" aria-pressed="false" aria-label="Thêm vào yêu thích">
                                        <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                        <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                                    </button>
                                </div>

                                <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                                    <input type="hidden" name="hanh_dong" value="them">
                                    <input type="hidden" name="id" value="<?= $mon['id'] ?>">
                                    <input type="hidden" name="so_luong" value="1">
                                    <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
// Script xử lý nút Yêu thích và cập nhật Header
document.addEventListener('DOMContentLoaded', function() {
    const dsNutYeuThich = document.querySelectorAll('.nut-yeu-thich');
    
    // [HÀM MỚI] Đếm số tim đã lưu và sửa chữ trên thanh Header
    function capNhatHeader() {
        let count = 0;
        for (let i = 0; i < localStorage.length; i++) {
            if (localStorage.key(i).startsWith('yeu_thich_')) count++;
        }
        
        // Quét tự động để tìm thẻ chứa chữ "Yêu thích:" trên toàn bộ trang
        const theBao = document.querySelectorAll('a, button, span, div');
        theBao.forEach(function(el) {
            if (el.childNodes.length === 1 && el.textContent.includes('Yêu thích:')) {
                el.textContent = '♥ Yêu thích: ' + count;
            }
        });
    }

    // Cập nhật số đếm ngay khi vừa mở trang
    capNhatHeader();

    dsNutYeuThich.forEach(function(nut) {
        const idMonHoc = nut.getAttribute('data-yeu-thich-id');
        const iconTraiTim = nut.querySelector('.nut-yeu-thich__icon');
        
        // 1. Kiểm tra trạng thái đã lưu
        if (localStorage.getItem('yeu_thich_' + idMonHoc) === 'co') {
            nut.classList.add('nut-yeu-thich--da-chon');
            iconTraiTim.textContent = '♥';
        }

        // 2. Lắng nghe sự kiện click
        nut.addEventListener('click', function() {
            this.classList.toggle('nut-yeu-thich--da-chon');
            
            if (this.classList.contains('nut-yeu-thich--da-chon')) {
                iconTraiTim.textContent = '♥';
                localStorage.setItem('yeu_thich_' + idMonHoc, 'co');
            } else {
                iconTraiTim.textContent = '♡';
                localStorage.removeItem('yeu_thich_' + idMonHoc);
            }
            
            // Cập nhật lại số đếm sau mỗi lần click
            capNhatHeader();
        });
    });
});
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>