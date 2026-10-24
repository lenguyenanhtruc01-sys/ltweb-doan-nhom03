<?php
/**
 * Giao diện bình chọn trang Hợp: số phiếu và tỉ lệ.
 * Thử: mở gioithieu.php#binh-chon; tổng bằng 0 không chia cho 0.
 * Mọi dữ liệu động xuất qua e().
 */
use App\Data\KhoBinhChon;
?>
<section class="the-noi-dung" id="binh-chon" aria-labelledby="tieu-de-binh-chon">
    <h2 class="the-noi-dung__tieu-de" id="tieu-de-binh-chon">Bình chọn trang cá nhân</h2>
    <p>Bạn đánh giá thế nào về trang cá nhân của Phan Văn Hợp?</p>
    <?php if ($hopVoteMessage !== ''): ?><p role="status"><?= e($hopVoteMessage) ?></p><?php endif; ?>
    <?php if ($hopVoteError !== ''): ?>
        <p role="status"><?= e($hopVoteError) ?></p>
    <?php else: ?>
        <?php if ($hopVoteUser !== false): ?>
        <form method="post" action="gioithieu.php#binh-chon" class="cong-cu-form">
            <input type="hidden" name="hanh_dong" value="binh_chon">
            <input type="hidden" name="csrf_hop_vote" value="<?= e($hopVoteToken) ?>">
            <fieldset><legend>Chọn một đánh giá</legend>
            <?php foreach (KhoBinhChon::TUY_CHON as $key => $label): ?>
                <p><label><input type="radio" name="tuy_chon" value="<?= e($key) ?>" required> <?= e($label) ?></label></p>
            <?php endforeach; ?>
            </fieldset>
            <button type="submit" class="btn-net">Bình chọn</button>
        </form>
        <?php else: ?>
            <p>Vui lòng <a href="../../dang-nhap.php">đăng nhập</a> bằng tài khoản MySQL để bình chọn.</p>
        <?php endif; ?>
    <?php endif; ?>
    <h3>Thống kê bình chọn</h3>
    <?php if ($hopVoteReady): ?>
        <ul>
        <?php foreach (KhoBinhChon::TUY_CHON as $key => $label): ?>
            <li><?= e($label) ?>: <strong><?= e($hopVoteCounts[$key]) ?></strong> phiếu
                (<?= e(number_format(array_sum($hopVoteCounts) > 0
                    ? $hopVoteCounts[$key] * 100 / array_sum($hopVoteCounts) : 0, 1, ',', '.')) ?>%)</li>
        <?php endforeach; ?>
        </ul>
        <p>Tổng số phiếu: <?= e(array_sum($hopVoteCounts)) ?></p>
    <?php else: ?><p>Chưa tải được thống kê.</p><?php endif; ?>
</section>
