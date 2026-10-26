-- database/schema.sql
-- Tạo lại CSDL từ đầu (chạy lại được nhiều lần)
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS ltweb_nhom03;
CREATE DATABASE ltweb_nhom03
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE ltweb_nhom03;

-- ============================================
-- 1. BẢNG NGƯỜI DÙNG (Phục vụ Chức năng 4)
-- ============================================
CREATE TABLE nguoi_dung (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(254) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    vai_tro ENUM('quan_tri', 'khach_hang') NOT NULL DEFAULT 'khach_hang',
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. BẢNG DANH MỤC (Phục vụ Chức năng 2)
-- ============================================
CREATE TABLE danh_muc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- ============================================
-- 3. BẢNG MÔN HỌC (Phục vụ Chức năng 2)
-- ============================================
CREATE TABLE mon_hoc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    danh_muc_id INT UNSIGNED NOT NULL,
    ma_mon VARCHAR(20) NOT NULL UNIQUE,
    ten_mon VARCHAR(200) NOT NULL,
    so_tin_chi INT UNSIGNED NOT NULL CHECK (so_tin_chi > 0),
    mo_ta TEXT NULL,
    anh VARCHAR(255) NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Không ghi ON DELETE: cấm xóa danh mục còn môn học (lỗi 1451)
    FOREIGN KEY (danh_muc_id) REFERENCES danh_muc(id)
);

-- ============================================
-- 4. BẢNG LIÊN HỆ (Thay cho storage/lien-he.jsonl)
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
-- 5. BẢNG KẾ HOẠCH HỌC TẬP (Thay cho Session giỏ hàng)
-- ============================================
CREATE TABLE ke_hoach_hoc_tap (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NULL, -- NULL nếu là khách (chưa đăng nhập)
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE SET NULL
);

-- ============================================
-- 6. BẢNG CHI TIẾT KẾ HOẠCH (Quan hệ n-n)
-- ============================================
CREATE TABLE chi_tiet_ke_hoach (
    ke_hoach_id INT UNSIGNED NOT NULL,
    mon_hoc_id INT UNSIGNED NOT NULL,
    so_luong SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (ke_hoach_id, mon_hoc_id),
    FOREIGN KEY (ke_hoach_id) REFERENCES ke_hoach_hoc_tap(id) ON DELETE CASCADE,
    FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE CASCADE
);

-- ============================================
-- 7. BẢNG THÀNH VIÊN (Phục vụ Phần B)
-- ============================================
CREATE TABLE thanh_vien (
    mssv CHAR(10) PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    nguoi_dung_id INT UNSIGNED NULL UNIQUE,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE SET NULL
);

-- ============================================
-- 8. BẢNG RIÊNG CỦA THÀNH VIÊN (Ví dụ: tv_luu_but)
-- ============================================
CREATE TABLE tv_luu_but (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ten_khach VARCHAR(50) NOT NULL,
    loi_nhan VARCHAR(500) NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

-- ============================================
-- CHỈ MỤC (Index) cho các cột hay lọc, sắp xếp
-- ============================================
CREATE INDEX idx_mon_hoc_ten ON mon_hoc (ten_mon);
CREATE INDEX idx_mon_hoc_tin_chi ON mon_hoc (so_tin_chi);

CREATE TABLE IF NOT EXISTS tv_binh_chon (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    tuy_chon ENUM('huu_ich', 'sang_tao', 'de_hieu') NOT NULL,
    UNIQUE KEY uq_vote (mssv, nguoi_dung_id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tv_tra_cuu_mang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    muc_tieu VARCHAR(253) NOT NULL,
    dia_chi_ip VARCHAR(45) NOT NULL,
    loai_ip ENUM('IPv4', 'IPv6') NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mssv_id (mssv, id),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ============================================
-- PHẦN B - BẢNG RIÊNG CỦA LÊ NGUYỄN ANH TRÚC
-- Danh sách dự án cá nhân
-- ============================================
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
