<?php
declare(strict_types=1);

/**
 * Tệp: quan-tri.php
 * Chức năng: Trang quản trị, hiển thị danh sách liên hệ đã nhận.
 */

require_once __DIR__ . '/inc/config.php';

$nguoiDungHienTai = yeuCauQuyen('quan_tri');

use App\Data\KhoLienHe;

$khoLienHe = new KhoLienHe(db());

$danhSachLienHe = $khoLienHe->tatCa();

// Đổi mã lưu trong dữ liệu liên hệ thành nhãn giống ở form liên hệ.
$nhanChuDe = [
    'gpa' => 'Lỗi tính GPA',
    'diem' => 'Lỗi nhập điểm',
    'taikhoan' => 'Vấn đề tài khoản',
    'tailieu' => 'Tài liệu học tập',
    'gopy' => 'Góp ý cải thiện hệ thống',
    'khac' => 'Khác',
];

$tieuDe = 'Quản trị hệ thống';
$trang = 'quan-tri';
$goc = '';
$cssTrang = 'css/quan-tri.css';

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh quan-tri">
    <div class="quan-tri__dau-trang">
        <div>
            <h1>Trang quản trị hệ thống EduGPA</h1>
            <p><a class="nut nut--chinh" href="quan-tri-mon-hoc.php">Quản lý môn học</a></p>

            <p class="quan-tri__loi-chao">
                Xin chào,
                <strong>
                    <?= e($nguoiDungHienTai['ho_ten']) ?>
                </strong>
            </p>
        </div>


    </div>

    <section class="quan-tri__noi-dung">
        <h2>Danh sách các liên hệ đã nhận</h2>

        <p class="quan-tri__mo-ta">
            Các thông tin liên hệ gửi từ người dùng được hiển thị
            theo thứ tự mới nhất lên trước.
        </p>

        <?php if (empty($danhSachLienHe)): ?>

            <div class="quan-tri__rong">
                <p>
                    Chưa có liên hệ nào được gửi tới hệ thống.
                </p>
            </div>

        <?php else: ?>

            <div class="bang-cuon">
                <table class="bang-lien-he">
                    <caption>
                        Danh sách liên hệ từ người dùng
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">Thời gian</th>
                            <th scope="col">Họ tên</th>
                            <th scope="col">Email / SĐT</th>
                            <th scope="col">Chủ đề</th>
                            <th scope="col">Ưu tiên</th>
                            <th scope="col">Nội dung</th>
                            <th scope="col">Hình ảnh</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($danhSachLienHe as $lh): ?>

                            <tr>
                                <td>
                                    <?= e(
                                        (string) (
                                            $lh['thoiGian']
                                            ?? 'N/A'
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e(
                                            (string) (
                                                $lh['hoTen']
                                                ?? ''
                                            )
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <div>
                                        <?= e(
                                            (string) (
                                                $lh['email']
                                                ?? ''
                                            )
                                        ) ?>
                                    </div>

                                    <?php if (!empty($lh['sdt'])): ?>
                                        <div class="thong-tin-phu">
                                            <?= e(
                                                (string) $lh['sdt']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e(
                                        $nhanChuDe[(string) ($lh['chuDe'] ?? '')]
                                        ?? (string) ($lh['chuDe'] ?? '')
                                    ) ?>
                                </td>

                                <td>
                                    <?= e(
                                        (string) (
                                            $lh['uuTien']
                                            ?? ''
                                        )
                                    ) ?>
                                </td>

                                <td class="noi-dung-lien-he">
                                    <?= nl2br(
                                        e(
                                            (string) (
                                                $lh['noiDung']
                                                ?? ''
                                            )
                                        )
                                    ) ?>
                                </td>

                                <td class="anh-lien-he">
                                    <?php if (!empty($lh['anh'])): ?>

                                        <img
                                            src="<?= e(
                                                'uploads/' . $lh['anh']
                                            ) ?>"
                                            alt="Ảnh đính kèm của liên hệ"
                                            class="anh-dinh-kem"
                                        >

                                    <?php else: ?>

                                        <span class="khong-co-anh">
                                            Không có ảnh
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>
</main>

<?php
require __DIR__ . '/inc/footer.php';
?>
