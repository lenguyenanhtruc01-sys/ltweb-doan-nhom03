<?php
const TEN_COOKIE_GHI_NHO = 'edugpa_ghi_nho';
const THOI_HAN_GHI_NHO = 2592000; // 30 ngày

function dungHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');
}

function tuyChonCookie(int $hetHan): array
{
    return [
        'expires' => $hetHan,
        'path' => '/',
        'secure' => dungHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function docCookieGhiNho(): ?array
{
    $giaTri = $_COOKIE[TEN_COOKIE_GHI_NHO] ?? null;
    if (!is_string($giaTri)) {
        return null;
    }

    $phan = explode(':', $giaTri, 2);
    if (count($phan) !== 2) {
        return null;
    }

    [$selector, $token] = $phan;
    if (!preg_match('/\\A[a-f0-9]{18}\\z/D', $selector)
        || !preg_match('/\\A[a-f0-9]{64}\\z/D', $token)) {
        return null;
    }

    return [$selector, $token];
}

function xoaCookieGhiNho(): void
{
    setcookie(TEN_COOKIE_GHI_NHO, '', tuyChonCookie(time() - 3600));
    unset($_COOKIE[TEN_COOKIE_GHI_NHO]);
}

function taoPhienGhiNho(int $uid): void
{
    $selector = bin2hex(random_bytes(9));
    $token = bin2hex(random_bytes(32));
    $hetHan = time() + THOI_HAN_GHI_NHO;

    $st = db()->prepare(
        'INSERT INTO phien_ghi_nho (selector, nguoi_dung_id, token_hash, het_han)
         VALUES (?, ?, ?, FROM_UNIXTIME(?))'
    );
    $st->execute([$selector, $uid, hash('sha256', $token), $hetHan]);

    setcookie(
        TEN_COOKIE_GHI_NHO,
        $selector . ':' . $token,
        tuyChonCookie($hetHan)
    );
}

function xoaGhiNhoHienTai(): void
{
    $duLieu = docCookieGhiNho();
    if ($duLieu !== null) {
        [$selector] = $duLieu;
        $st = db()->prepare('DELETE FROM phien_ghi_nho WHERE selector = ?');
        $st->execute([$selector]);
    }

    xoaCookieGhiNho();
}

function khoiPhucDangNhapTuCookie(): void
{
    if (isset($_SESSION['uid'])) {
        return;
    }

    $duLieu = docCookieGhiNho();
    if ($duLieu === null) {
        return;
    }
    [$selector, $token] = $duLieu;

    $st = db()->prepare(
        'SELECT nguoi_dung_id, token_hash
         FROM phien_ghi_nho
         WHERE selector = ? AND het_han > NOW()'
    );
    $st->execute([$selector]);
    $phien = $st->fetch();

    if (!$phien || !hash_equals($phien['token_hash'], hash('sha256', $token))) {
        if ($phien) {
            $xoa = db()->prepare('DELETE FROM phien_ghi_nho WHERE selector = ?');
            $xoa->execute([$selector]);
        }
        xoaCookieGhiNho();
        return;
    }

    // Xoay token sau mỗi lần dùng để hạn chế rủi ro token bị đánh cắp.
    $xoa = db()->prepare('DELETE FROM phien_ghi_nho WHERE selector = ?');
    $xoa->execute([$selector]);

    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $phien['nguoi_dung_id'];
    taoPhienGhiNho((int) $phien['nguoi_dung_id']);
}

/**
 * Tệp: inc/xac-thuc.php
 * Chức năng: Xác thực và Phân quyền (Slide 38 - 42)
 */

// Hàm lấy thông tin người dùng hiện tại (nếu đã đăng nhập)
function nguoiDung(): ?array
{
    static $daTai = false;
    static $nd = null;

    // Trong cùng một request, chỉ truy vấn user một lần.
    if ($daTai) {
        return $nd;
    }
    $daTai = true;

    // Session chỉ giữ uid; thông tin còn lại đọc từ database.
    $uid = $_SESSION['uid'] ?? null;

    if (!is_int($uid) && !(is_string($uid) && ctype_digit($uid))) {
        return null;
    }

    $uid = (int) $uid;

    if ($uid < 1) {
        return null;
    }

    $st = db()->prepare(
        'SELECT id, ten_dang_nhap, email, ho_ten, vai_tro
         FROM nguoi_dung
         WHERE id = ?'
    );
    $st->execute([$uid]);

    $nd = $st->fetch() ?: null;

    return $nd;
}

// Hàm bắt buộc phải đăng nhập mới được vào
function yeuCauDangNhap(): array
{
    $nd = nguoiDung();
    if ($nd === null) {
        // Chưa đăng nhập thì đá về trang đăng nhập
        header('Location: dang-nhap.php');
        exit;
    }
    return $nd;
}

// Hàm bắt buộc phải có quyền cụ thể (VD: 'quan_tri')
function yeuCauQuyen(string $vaiTro): array
{
    $nd = yeuCauDangNhap();

    // Nếu vai trò không khớp -> Báo lỗi 403 (Slide 42)
    if ($nd['vai_tro'] !== $vaiTro) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }

    return $nd;
}
