<?php
declare(strict_types=1);

/**
 * Đọc và ghi liên hệ JSONL, trả bản mới nhất trước.
 * Kiểm tra lỗi đọc/ghi thay vì truyền false vào xử lý mảng.
 * Thử gửi liên hệ rồi xem quản trị; hiện còn dùng JSONL, chưa chuyển sang PDO.
 */
namespace App\Data;

class KhoLienHe
{
    public function __construct(private string $tep) {}

    public function them(array $lh): void
    {
        $thuMuc = dirname($this->tep);
<?php
declare(strict_types=1);

namespace App\Data;

use PDO;

class KhoLienHe
{
    public function __construct(private PDO $db) {}

    public function them(array $lh): void
    {
        $st = $this->db->prepare(
            'INSERT INTO lien_he
                (ho_ten, email, so_dien_thoai, chu_de, uu_tien, noi_dung, anh)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $lh['hoTen'],
            $lh['email'],
            $lh['sdt'] !== '' ? $lh['sdt'] : null,
            $lh['chuDe'],
            $lh['uuTien'],
            $lh['noiDung'],
            $lh['anh'],
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function tatCa(): array
    {
        $st = $this->db->query(
            'SELECT id, ho_ten, email, so_dien_thoai, chu_de, uu_tien,
                    noi_dung, anh, ngay_tao
             FROM lien_he
             ORDER BY ngay_tao DESC, id DESC'
        );

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
            $st->fetchAll()
        );
    }
}

        if (!is_dir($thuMuc) && !mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu liên hệ.');
        }

        $dong = json_encode($lh,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $soByteDaGhi = file_put_contents(
            $this->tep,
            $dong . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($soByteDaGhi === false) {
            throw new \RuntimeException('Không thể lưu liên hệ.');
        }
    }

    public function tatCa(): array                  // mới nhất đứng đầu
    {
        if (!is_file($this->tep)) return [];
        $dong = @file($this->tep,
                     FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($dong === false) {
            throw new \RuntimeException('Không thể đọc danh sách liên hệ.');
        }
        $ds = array_map(fn($d) => json_decode($d, true), $dong);
        return array_reverse(array_filter($ds, 'is_array'));
    }
}
