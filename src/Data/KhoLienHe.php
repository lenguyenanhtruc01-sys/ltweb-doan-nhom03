<?php

declare(strict_types=1);

namespace App\Data;

use PDO;

/**
 * Lớp truy cập dữ liệu liên hệ bằng MySQL.
 * Nhận kết nối PDO từ hàm db().
 */
final class KhoLienHe
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Lưu liên hệ vào bảng lien_he.
     *
     * @param array<string, mixed> $lh
     */
    public function them(array $lh): void
    {
        $sql = '
            INSERT INTO lien_he (
                ho_ten,
                email,
                so_dien_thoai,
                chu_de,
                uu_tien,
                noi_dung,
                anh
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $lh['hoTen'],
            $lh['email'],
            $lh['sdt'] !== '' ? $lh['sdt'] : null,
            $lh['chuDe'],
            $lh['uuTien'],
            $lh['noiDung'],
            $lh['anh'] ?? null,
        ]);
    }

    /**
     * Lấy danh sách liên hệ, mới nhất trước.
     *
     * @return list<array<string, mixed>>
     */
    public function tatCa(): array
    {
        $sql = '
            SELECT
                id,
                ho_ten,
                email,
                so_dien_thoai,
                chu_de,
                uu_tien,
                noi_dung,
                anh,
                ngay_tao
            FROM lien_he
            ORDER BY ngay_tao DESC, id DESC
        ';

        $stmt = $this->db->query($sql);

        return array_map(
            static fn(array $row): array => [
                'id' => (int) $row['id'],
                'hoTen' => $row['ho_ten'],
                'email' => $row['email'],
                'sdt' => $row['so_dien_thoai'],
                'chuDe' => $row['chu_de'],
                'uuTien' => $row['uu_tien'],
                'noiDung' => $row['noi_dung'],
                'anh' => $row['anh'],
                'thoiGian' => $row['ngay_tao'],
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
