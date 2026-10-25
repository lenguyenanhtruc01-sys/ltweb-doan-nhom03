<?php
declare(strict_types=1);

/**
 * Truy cập PDO cho đăng ký và yêu cầu quên/đặt lại mật khẩu.
 * Tạo tài khoản khách hàng; đổi mật khẩu và thu hồi cookie ghi nhớ trong transaction.
 * Thử đăng ký trùng/giả vai trò, mã hết hạn/dùng lại và hai lần đổi cùng một mã.
 */
namespace App\Data;

use PDO;

final class KhoNguoiDung
{
    public function __construct(private PDO $pdo) {}

    public function dangKy(string $tenDangNhap, string $email, string $matKhauBam, string $hoTen): int
    {
        $st = $this->pdo->prepare(
            "INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, vai_tro)
             VALUES (?, ?, ?, ?, 'khach_hang')"
        );
        $st->execute([$tenDangNhap, $email, $matKhauBam, $hoTen]);
        return (int) $this->pdo->lastInsertId();
    }

    public function taoYeuCauDatLai(string $email, string $tokenHash): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Cùng khóa tài khoản như khi đổi mật khẩu để tránh hai mã cùng hiệu lực.
            $st = $this->pdo->prepare('SELECT id FROM nguoi_dung WHERE email=? FOR UPDATE');
            $st->execute([$email]);
            $uid = $st->fetchColumn();
            if ($uid === false) {
                $this->pdo->rollBack();
                return false;
            }
            $st = $this->pdo->prepare('UPDATE yeu_cau_dat_lai_mat_khau SET da_dung_luc=NOW()
                WHERE nguoi_dung_id=? AND da_dung_luc IS NULL');
            $st->execute([(int) $uid]);
            $st = $this->pdo->prepare('INSERT INTO yeu_cau_dat_lai_mat_khau
                (nguoi_dung_id,token_hash,het_han) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))');
            $st->execute([(int) $uid, $tokenHash]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $loi) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $loi;
        }
    }

    public function timYeuCauDatLai(string $token): ?array
    {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $token) !== 1) return null;
        $st = $this->pdo->prepare('SELECT id,nguoi_dung_id FROM yeu_cau_dat_lai_mat_khau
            WHERE token_hash=? AND da_dung_luc IS NULL AND het_han>NOW()');
        $st->execute([hash('sha256', $token)]);
        return $st->fetch() ?: null;
    }

    public function datLaiMatKhau(string $token, string $matKhauBam): bool
    {
        $this->pdo->beginTransaction();
        try {
            $yeuCau = $this->timYeuCauDatLai($token);
            if ($yeuCau === null) {
                $this->pdo->rollBack();
                return false;
            }
            $uid = (int) $yeuCau['nguoi_dung_id'];
            $st = $this->pdo->prepare('SELECT id FROM nguoi_dung WHERE id=? FOR UPDATE');
            $st->execute([$uid]);
            if ($st->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            // Kiểm tra lại ngay khi ghi: mã có thể vừa bị dùng/hủy sau lần SELECT.
            $st = $this->pdo->prepare('UPDATE yeu_cau_dat_lai_mat_khau SET da_dung_luc=NOW()
                WHERE id=? AND token_hash=? AND da_dung_luc IS NULL AND het_han>NOW()');
            $st->execute([(int) $yeuCau['id'], hash('sha256', $token)]);
            if ($st->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $st = $this->pdo->prepare('UPDATE nguoi_dung SET mat_khau=? WHERE id=?');
            $st->execute([$matKhauBam, $uid]);
            $st = $this->pdo->prepare('DELETE FROM phien_ghi_nho WHERE nguoi_dung_id=?');
            $st->execute([$uid]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $loi) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $loi;
        }
    }
}
