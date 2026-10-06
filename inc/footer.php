<?php
/**
 * inc/footer.php
 * Footer dùng chung của website EduGPA - Nhóm 03.
 * Hiển thị thông tin website, liên kết điều hướng.
 * Nạp JavaScript chung và JavaScript riêng từng trang.
 * Kiểm thử bằng cách mở trang PHP trên localhost.
 */

$goc ??= '';
$scriptsTrang ??= [];
?>

<!-- ================= FOOTER ================= -->
<footer class="chan-trang">

    <div class="bao chan-trang__noi-dung">

        <div>

            <p class="chan-trang__thuong-hieu">
                EduGPA
            </p>

            <p class="chan-trang__mo-ta">
                Hệ thống hỗ trợ tra cứu
                và theo dõi thông tin học tập
                cho sinh viên.
            </p>

        </div>

        <nav
            class="chan-trang__dieu-huong"
            aria-label="Điều hướng cuối trang">

            <a href="<?= e($goc) ?>gioi-thieu.php">
                Giới thiệu
            </a>

            <a href="<?= e($goc) ?>lien-he.php">
                Liên hệ
            </a>

        </nav>

        <p class="chan-trang__ban-quyen">
            &copy; 2026 Nhóm 03 - Khoa Toán - Tin.
        </p>

    </div>

</footer>

<!-- JavaScript dùng chung -->
<script
    type="module"
    src="<?= e($goc) ?>js/main.js">
</script>

<!-- JavaScript riêng của từng trang, nếu có -->
<?php foreach ($scriptsTrang as $script): ?>
    <script
        type="module"
        src="<?= e($goc . $script) ?>">
    </script>
<?php endforeach; ?>

</body>
</html>
