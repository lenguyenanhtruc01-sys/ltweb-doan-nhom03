<?php
declare(strict_types=1);

/**
 * Lưu tên ảnh đại diện trong MySQL, tệp ảnh nằm trong uploads.
 * Thay ảnh trong transaction, kiểm tra chủ trang bằng thanh_vien.nguoi_dung_id.
 * Thử khách/tài khoản khác bị chặn; chủ trang đổi ảnh rồi F5 không gửi lại.
 */
namespace App\Data;

class KhoAnhDaiDien
{
    public function __construct(private \PDO $pdo) {}

    public function laChuTrang(string $mssv, int $uid): bool
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM thanh_vien WHERE mssv=? AND nguoi_dung_id=?');
        $st->execute([$mssv, $uid]);
        return (int) $st->fetchColumn() === 1;
    }

    public function lay(string $mssv): ?string
    {
        $st = $this->pdo->prepare('SELECT ten_tep FROM tv_anh_dai_dien WHERE mssv=?');
        $st->execute([$mssv]);
        $ten = $st->fetchColumn();
        return is_string($ten) && preg_match('/^anh_[a-f0-9]{32}\.(jpg|png|webp)$/D', $ten) === 1
            ? $ten : null;
    }

    public function luu(string $mssv, int $uid, string $ten): ?string
    {
        if (preg_match('/^anh_[a-f0-9]{32}\.(jpg|png|webp)$/D', $ten) !== 1) {
            throw new \InvalidArgumentException('Tên ảnh không hợp lệ.');
        }
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('SELECT nguoi_dung_id FROM thanh_vien WHERE mssv=? FOR UPDATE');
            $st->execute([$mssv]);
            $chu = $st->fetchColumn();
            if ($uid <= 0 || $chu === false || (int) $chu !== $uid) throw new \RuntimeException('Chỉ chủ trang mới được đổi ảnh đại diện.');
            $tenCu = $this->lay($mssv);
            $st = $this->pdo->prepare('INSERT INTO tv_anh_dai_dien (mssv,ten_tep) VALUES (?,?)
                ON DUPLICATE KEY UPDATE ten_tep=VALUES(ten_tep), cap_nhat_luc=CURRENT_TIMESTAMP');
            $st->execute([$mssv, $ten]);
            $this->pdo->commit();
            return $tenCu;
        } catch (\Throwable $loi) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $loi;
        }
    }
}
