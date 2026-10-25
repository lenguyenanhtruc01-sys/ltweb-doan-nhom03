-- Nang cap database hien co, khong xoa du lieu.
-- Import truoc khi dung gio va yeu thich theo tai khoan.
USE ltweb_nhom03;
CREATE TABLE IF NOT EXISTS gio_hang_nguoi_dung (
    nguoi_dung_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    so_luong SMALLINT UNSIGNED NOT NULL CHECK (so_luong BETWEEN 1 AND 5),
    PRIMARY KEY (nguoi_dung_id, mon_hoc_id),
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS mon_yeu_thich (
    nguoi_dung_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (nguoi_dung_id, mon_hoc_id),
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
