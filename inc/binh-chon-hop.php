<?php
declare(strict_types=1);

/**
 * Chuẩn bị bình chọn trước header trang Hợp.
 * Dùng uid từ phần đăng nhập chung; không nhận ID tài khoản từ form.
 * Thử: vote, vote lại, F5; thông báo chỉ hiển thị một lần.
 */
use App\Data\KhoBinhChon;
require_once __DIR__ . '/db.php';

$hopVoteMssv = '3120224064';
$hopVotePost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && layChuoiDauVao($_POST['hanh_dong'] ?? '') === 'binh_chon';
$hopVoteMessage = layChuoiDauVao($_SESSION['hop_vote_flash'] ?? '');
unset($_SESSION['hop_vote_flash']);
$hopVoteError = '';
$hopVoteCounts = array_fill_keys(array_keys(KhoBinhChon::TUY_CHON), 0);
$hopVoteReady = false;
$hopVoteUserRaw = $_SESSION['uid'] ?? null;
$hopVoteUser = (is_int($hopVoteUserRaw) || is_string($hopVoteUserRaw))
    ? filter_var($hopVoteUserRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if (!is_string($_SESSION['hop_vote_csrf'] ?? null) || $_SESSION['hop_vote_csrf'] === '') {
    $_SESSION['hop_vote_csrf'] = bin2hex(random_bytes(32));
}
$hopVoteToken = $_SESSION['hop_vote_csrf'];

try {
    $pdoHopVote = db();
    $khoHopVote = new KhoBinhChon($pdoHopVote);
    if ($hopVotePost) {
        $received = $_POST['csrf_hop_vote'] ?? null;
        if (!is_string($received) || !hash_equals($hopVoteToken, $received)) {
            throw new DomainException('Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.');
        }
        if ($hopVoteUser === false) {
            throw new DomainException('Vui lòng đăng nhập tài khoản MySQL trước khi bình chọn.');
        }
        $khoHopVote->boPhieu($hopVoteMssv, $hopVoteUser, layChuoiDauVao($_POST['tuy_chon'] ?? ''));
        $_SESSION['hop_vote_flash'] = 'Bình chọn thành công.';
    }
    foreach ($khoHopVote->thongKe($hopVoteMssv) as $row) {
        if (array_key_exists($row['tuy_chon'], $hopVoteCounts)) {
            $hopVoteCounts[$row['tuy_chon']] = (int) $row['so_phieu'];
        }
    }
    $hopVoteReady = true;
} catch (DomainException $error) {
    $_SESSION['hop_vote_flash'] = $error->getMessage();
} catch (Throwable $error) {
    error_log((string) $error);
    $hopVoteError = 'Bình chọn chưa sẵn sàng. Cần cấu hình kết nối MySQL và bảng bình chọn.';
    if ($hopVotePost) {
        $_SESSION['hop_vote_flash'] = 'Chưa gửi được bình chọn. Vui lòng thử lại sau.';
    }
}
if ($hopVotePost) {
    header('Location: gioithieu.php#binh-chon', true, 303);
    exit;
}
