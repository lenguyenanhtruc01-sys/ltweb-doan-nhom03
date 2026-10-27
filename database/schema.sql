-- database/schema.sql
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS ltweb_nhom03;
CREATE DATABASE ltweb_nhom03 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ltweb_nhom03;

-- ============================================
-- 1. NGƯỜI DÙNG
-- ============================================
CREATE TABLE nguoi_dung (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    vai_tro ENUM('quan_tri', 'khach_hang') NOT NULL DEFAULT 'khach_hang',
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Phiên đăng nhập lâu dài: cookie chỉ chứa selector + token ngẫu nhiên.
-- CSDL chỉ lưu hash của token để tránh dùng lại nếu dữ liệu bị lộ.
CREATE TABLE phien_ghi_nho (
    selector CHAR(18) PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    het_han DATETIME NOT NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    INDEX idx_phien_ghi_nho_het_han (het_han)
);

-- Mã một lần để đặt lại mật khẩu. Mã hết hạn sau 30 phút ở tầng PHP.
CREATE TABLE yeu_cau_dat_lai_mat_khau (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    het_han DATETIME NOT NULL,
    da_dung_luc DATETIME NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    INDEX idx_dat_lai_nguoi_dung (nguoi_dung_id),
    INDEX idx_dat_lai_het_han (het_han)
);

-- ============================================
-- 2. DANH MỤC
-- ============================================
CREATE TABLE danh_muc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- ============================================
-- 3. MÔN HỌC
-- ============================================
CREATE TABLE mon_hoc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    danh_muc_id INT UNSIGNED NOT NULL,
    ma_mon VARCHAR(20) NOT NULL UNIQUE,
    ten_mon VARCHAR(200) NOT NULL,
    so_tin_chi INT UNSIGNED NOT NULL CHECK (so_tin_chi > 0),
    so_cho INT UNSIGNED NOT NULL DEFAULT 50,
    mo_ta TEXT NULL,
    anh VARCHAR(255) NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (danh_muc_id) REFERENCES danh_muc(id)
);

-- ============================================
-- 4. LIÊN HỆ
-- ============================================
CREATE TABLE lien_he (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20) NULL,
    chu_de VARCHAR(100) NOT NULL,
    uu_tien ENUM('thap', 'trungbinh', 'cao') NOT NULL DEFAULT 'trungbinh',
    noi_dung TEXT NOT NULL,
    anh VARCHAR(255) NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 5. KẾ HOẠCH HỌC TẬP (đã có trang_thai + ghi_chu)
-- ============================================
CREATE TABLE ke_hoach_hoc_tap (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NULL,
    trang_thai VARCHAR(20) NOT NULL DEFAULT 'cho_duyet'
        CHECK (trang_thai IN ('cho_duyet', 'da_duyet', 'tu_choi')),
    ghi_chu VARCHAR(500) NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CHI TIẾT KẾ HOẠCH (có snapshot)
-- ============================================
CREATE TABLE chi_tiet_ke_hoach (
    ke_hoach_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    ma_mon VARCHAR(20) NOT NULL,
    ten_mon VARCHAR(200) NOT NULL,
    so_tin_chi INT UNSIGNED NOT NULL,
    so_luong SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (ke_hoach_id, mon_hoc_id),
    FOREIGN KEY (ke_hoach_id) REFERENCES ke_hoach_hoc_tap(id) ON DELETE CASCADE,
    -- Giữ chi tiết kế hoạch: môn đã được đăng ký không được xóa (lỗi 1451).
    CONSTRAINT fk_chi_tiet_ke_hoach_mon_hoc
        FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. THÀNH VIÊN (Phần B)
-- ============================================
CREATE TABLE thanh_vien (
    mssv CHAR(10) PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    nguoi_dung_id INT UNSIGNED NULL UNIQUE,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE SET NULL
);

CREATE TABLE tv_luu_but (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ten_khach VARCHAR(50) NOT NULL,
    loi_nhan VARCHAR(500) NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
);

-- Ảnh đại diện cá nhân: chỉ chủ trang được thay, tên tệp do server sinh.
CREATE TABLE tv_anh_dai_dien (
    mssv CHAR(10) PRIMARY KEY,
    ten_tep VARCHAR(255) NULL,
    cap_nhat_luc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
);

CREATE TABLE tv_luot_xem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ngay DATE NOT NULL,
    so_luot INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_tv_luot_xem_mssv_ngay (mssv, ngay),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_mon_hoc_ten ON mon_hoc (ten_mon);
CREATE INDEX idx_mon_hoc_tin_chi ON mon_hoc (so_tin_chi);

CREATE TABLE tv_binh_chon (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    tuy_chon ENUM('huu_ich', 'sang_tao', 'de_hieu') NOT NULL,
    UNIQUE KEY uq_vote (mssv, nguoi_dung_id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tv_tra_cuu_mang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    muc_tieu VARCHAR(253) NOT NULL,
    dia_chi_ip VARCHAR(45) NOT NULL,
    loai_ip ENUM('IPv4', 'IPv6') NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mssv_id (mssv, id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Bảng lưu danh sách món ăn
CREATE TABLE IF NOT EXISTS tv_mon_an (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ten_mon VARCHAR(100) NOT NULL,
    bua VARCHAR(20) NOT NULL DEFAULT 'bat_ky',
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check-in thời tiết trên trang cá nhân Duy; không nạp lượt mẫu.
CREATE TABLE IF NOT EXISTS tv_thoi_tiet_duy (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    trang_thai ENUM('nang', 'mua', 'may', 'giong_bao') NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_thoi_tiet_trang_thai (trang_thai),
    INDEX idx_thoi_tiet_mssv_trang_thai (mssv, trang_thai),
    CONSTRAINT fk_thoi_tiet_duy_thanh_vien FOREIGN KEY (mssv)
        REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu lịch sử oẳn tù tì
CREATE TABLE IF NOT EXISTS tv_lich_su_ott (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    nguoi_chon VARCHAR(10) NOT NULL,
    may_chon VARCHAR(10) NOT NULL,
    ket_qua VARCHAR(10) NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu kỹ năng và thời khóa biểu riêng của Huỳnh Phương Uyên.
CREATE TABLE tv_ky_nang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ten VARCHAR(120) NOT NULL,
    nhom ENUM('web', 'du-lieu', 'lam-viec') NOT NULL,
    UNIQUE KEY uq_tv_ky_nang (mssv, ten),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tv_thoi_khoa_bieu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ma_mon VARCHAR(20) NOT NULL,
    ten_mon VARCHAR(150) NOT NULL,
    ma_lop VARCHAR(20) NOT NULL,
    thu TINYINT UNSIGNED NOT NULL,
    tiet_bat_dau TINYINT UNSIGNED NOT NULL,
    tiet_ket_thuc TINYINT UNSIGNED NOT NULL,
    phong VARCHAR(30) NOT NULL,
    CHECK (thu BETWEEN 2 AND 8),
    CHECK (tiet_bat_dau BETWEEN 1 AND 12),
    CHECK (tiet_ket_thuc BETWEEN tiet_bat_dau AND 12),
    UNIQUE KEY uq_tv_thoi_khoa_bieu (mssv, ma_mon, thu, tiet_bat_dau),
    INDEX idx_tv_thoi_khoa_bieu (mssv, thu, tiet_bat_dau),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Giỏ hàng và yêu thích được lưu theo tài khoản.
CREATE TABLE gio_hang_nguoi_dung (
    nguoi_dung_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    so_luong SMALLINT UNSIGNED NOT NULL CHECK (so_luong BETWEEN 1 AND 5),
    PRIMARY KEY (nguoi_dung_id, mon_hoc_id),
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mon_yeu_thich (
    nguoi_dung_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (nguoi_dung_id, mon_hoc_id),
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dự án cá nhân của Anh Trúc.
CREATE TABLE IF NOT EXISTS tv_du_an (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ten_du_an VARCHAR(200) NOT NULL,
    mo_ta TEXT NOT NULL,
    cong_nghe VARCHAR(255) NOT NULL,
    nam_thuc_hien YEAR NOT NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (mssv)
        REFERENCES thanh_vien(mssv)
        ON DELETE CASCADE,

    INDEX idx_tv_du_an_mssv (mssv),
    INDEX idx_tv_du_an_nam (nam_thuc_hien)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- Kết quả riêng của người xem trang Trúc; pham_vi là SHA-256 của định danh máy chủ.
CREATE TABLE tv_truc_gpa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    pham_vi CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ket_qua JSON NOT NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_truc_gpa_pham_vi (mssv, pham_vi, id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tv_truc_quiz (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    pham_vi CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    cau_hoi VARCHAR(255) NOT NULL,
    da_chon ENUM('a','b','c','d') NOT NULL,
    dap_an ENUM('a','b','c','d') NOT NULL,
    chinh_xac TINYINT UNSIGNED NOT NULL CHECK (chinh_xac IN (0,1)),
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_truc_quiz_pham_vi (mssv, pham_vi, id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
