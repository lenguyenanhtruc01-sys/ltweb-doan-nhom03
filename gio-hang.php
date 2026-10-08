<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Services\KeHoachHocTap;

$keHoach = new KeHoachHocTap();
$ketQua = $keHoach->layDanhSachChiTiet();
$danhSach = $ketQua['danh_sach'];
$tongSoTinChi = $ketQua['tong_so_tin_chi'];

require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/header.php';
?>

    <main class="noi-dung-chinh">
        <div class="bao">
            <h1>Kế hoạch đăng ký môn học (Giỏ hàng)</h1>
            <p>Xem lại danh sách các học phần bạn đã chọn đưa vào kế hoạch học tập cá nhân.</p>

            <?php if (empty($danhSach)): ?>
                <div style="padding: 2rem; text-align: center; background: #f8f9fa; border-radius: 8px; margin-top: 1.5rem;">
                    <p>Kế hoạch học tập của bạn đang trống.</p>
                    <a href="danh-sach.php" class="nut nut--chinh" style="margin-top: 1rem; display: inline-block;">Khám phá danh sách môn học ngay</a>
                </div>
            <?php else: ?>
                <form action="xu-ly-gio-hang.php" method="POST">
                    <input type="hidden" name="action" value="cap_nhat">

                    <div class="bang-cuon" style="margin-top: 1.5rem;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #e9ecef; text-align: left;">
                                    <th style="padding: 10px; border: 1px solid #dee2e6;">Mã môn</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6;">Tên môn học</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Số tín chỉ</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Số lượng / Lớp</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Tổng tín chỉ</th>
                                    <th style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($danhSach as $item): ?>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?= htmlspecialchars($item['maMon'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 10px; border: 1px solid #dee2e6;">
                                            <a href="chi-tiet.php?id=<?= $item['id'] ?>">
                                                <?= htmlspecialchars($item['tenMon'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;"><?= $item['soTinChi'] ?></td>
                                        <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">
                                            <!-- Ô nhập số lượng có áp dụng min/max -->
                                            <input type="number" name="so_luong[<?= $item['id'] ?>]" value="<?= $item['soLuong'] ?>" min="1" max="5" style="width: 60px; text-align: center; padding: 4px;">
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;"><?= $item['tongTinChi'] ?></td>
                                        <td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">
                                            <a href="xu-ly-gio-hang.php?action=xoa&id=<?= $item['id'] ?>" style="color: #dc3545; text-decoration: none; font-weight: bold;" onclick="return confirm('Bạn có chắc muốn xóa môn này khỏi kế hoạch?');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: bold;">
                                    <td colspan="4" style="padding: 12px; border: 1px solid #dee2e6; text-align: right;">Tổng cộng toàn bộ tín chỉ đăng ký:</td>
                                    <td colspan="2" style="padding: 12px; border: 1px solid #dee2e6; color: #0d6efd; font-size: 1.1rem; text-align: center;"><?= $tongSoTinChi ?> Tín chỉ</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
                        <div>
                            <a href="danh-sach.php" class="nut" style="background: #6c757d; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">&larr; Tiếp tục chọn môn</a>
                            <a href="xu-ly-gio-hang.php?action=xoa_tat_ca" class="nut" style="background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin-left: 10px;" onclick="return confirm('Xóa sạch toàn bộ kế hoạch?');">Xóa tất cả</a>
                        </div>
                        <div>
                            <button type="submit" class="nut nut--chinh" style="padding: 0.5rem 1.5rem; cursor: pointer;">Cập nhật lại số lượng</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

<?php
require __DIR__ . '/inc/footer.php';
?>