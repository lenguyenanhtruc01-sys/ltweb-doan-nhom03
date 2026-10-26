<?php
declare(strict_types=1);

/**
 * Lưu, tìm và thu hồi phiên ghi nhớ bằng PDO; chỉ lưu hash của token.
 * Cookie, kiểm tra token và khôi phục session do inc/xac-thuc.php xử lý.
 * Thử phiên hợp lệ/hết hạn, xóa selector và thu hồi theo tài khoản.
 */
namespace App\Data;

use PDO;

final class KhoPhienGhiNho
{
    public function __construct(private PDO $pdo) {}

    public function them(string $selector, int $uid, string $tokenHash, int $hetHan): void
    {
        $st = $this->pdo->prepare('INSERT INTO phien_ghi_nho
            (selector,nguoi_dung_id,token_hash,het_han) VALUES (?,?,?,FROM_UNIXTIME(?))');
        $st->execute([$selector, $uid, $tokenHash, $hetHan]);
    }

    public function timConHan(string $selector): ?array
    {
        $st = $this->pdo->prepare('SELECT nguoi_dung_id,token_hash FROM phien_ghi_nho
            WHERE selector=? AND het_han>NOW()');
        $st->execute([$selector]);
        return $st->fetch() ?: null;
    }

    public function xoaTheoSelector(string $selector): void
    {
        $st = $this->pdo->prepare('DELETE FROM phien_ghi_nho WHERE selector=?');
        $st->execute([$selector]);
    }

    public function xoaCuaNguoiDung(int $uid): void
    {
        $st = $this->pdo->prepare('DELETE FROM phien_ghi_nho WHERE nguoi_dung_id=?');
        $st->execute([$uid]);
    }
}
