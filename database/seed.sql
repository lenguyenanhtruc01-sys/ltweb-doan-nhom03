-- database/seed.sql
-- Dữ liệu mẫu (chạy SAU schema.sql)
SET NAMES utf8mb4;
USE ltweb_nhom03;

-- ============================================
-- 1. TÀI KHOẢN MẪU (Mật khẩu của cả 3 tài khoản đều là: matkhau123)
-- ============================================
INSERT INTO nguoi_dung (id, ten_dang_nhap, mat_khau, ho_ten, vai_tro) VALUES
(1, 'admin', '$2y$12$qHb8qF.xgsQsDEN5nvxo6OsTDLDQmDQmTnjDdlNCp7MqAzd4fkWBG', 'Quản trị viên', 'quan_tri'),
(2, 'an', '$2y$12$u.0T5HsnlVJWcPLCbRUsIO9EXccEweLCYOD7R46J.TJ9bH4ju5eGC', 'Trần Văn An', 'khach_hang'),
(3, 'binh', '$2y$12$3ZoP8Zw46/1SUlmMu/unR.ZIvgTjCuo2NGWs2F9ttQBE.FmFo83bO', 'Nguyễn Thị Bình', 'khach_hang');

-- ============================================
-- 2. DANH MỤC MÔN HỌC
-- ============================================
INSERT INTO danh_muc (id, ten, slug) VALUES
(1, 'Môn Đại cương', 'mon-dai-cuong'),
(2, 'Môn Cơ sở ngành', 'mon-co-so-nganh'),
(3, 'Môn Chuyên ngành', 'mon-chuyen-nganh'),
(4, 'Môn Tự chọn', 'mon-tu-chon');

-- ============================================
-- 3. MÔN HỌC MẪU (Tạm thời lấy 8 môn để test)
-- ============================================
INSERT INTO mon_hoc (id, danh_muc_id, ma_mon, ten_mon, so_tin_chi, mo_ta) VALUES
(1, 1, 'MAT101', 'Giải tích 1', 4, 'Môn toán cơ bản cho sinh viên năm nhất'),
(2, 1, 'MAT102', 'Đại số tuyến tính', 3, 'Ma trận, hệ phương trình, không gian vector'),
(3, 2, 'INT101', 'Lập trình cơ bản', 3, 'Giới thiệu về lập trình với C/C++'),
(4, 2, 'INT102', 'Cấu trúc dữ liệu và giải thuật', 4, 'Mảng, danh sách liên kết, cây, đồ thị'),
(5, 3, 'WEB301', 'Thiết kế và lập trình Web', 3, 'HTML, CSS, JavaScript, PHP cơ bản'),
(6, 3, 'DB301', 'Cơ sở dữ liệu', 3, 'Mô hình quan hệ, SQL, chuẩn hóa'),
(7, 4, 'ENG201', 'Tiếng Anh chuyên ngành', 2, 'Tiếng Anh cho công nghệ thông tin'),
(8, 4, 'AI401', 'Nhập môn Trí tuệ nhân tạo', 3, 'Tổng quan về AI và học máy');

INSERT INTO thanh_vien (mssv, ho_ten)
SELECT '3120224064', 'Phan Văn Hợp'
WHERE NOT EXISTS (SELECT 1 FROM thanh_vien WHERE mssv = '3120224064');

INSERT INTO tv_tra_cuu_mang (mssv, muc_tieu, dia_chi_ip, loai_ip)
SELECT '3120224064', '127.0.0.1', '127.0.0.1', 'IPv4'
WHERE NOT EXISTS (SELECT 1 FROM tv_tra_cuu_mang WHERE mssv = '3120224064');

-- Một phiếu mẫu từ tài khoản khách có thật; không giả định ID của nhóm.
INSERT INTO tv_binh_chon (mssv, nguoi_dung_id, tuy_chon)
SELECT '3120224064', u.id, 'huu_ich' FROM nguoi_dung u
WHERE u.vai_tro = 'khach_hang'
  AND NOT EXISTS (SELECT 1 FROM tv_binh_chon b WHERE b.mssv = '3120224064' AND b.nguoi_dung_id = u.id)
ORDER BY u.id LIMIT 1;
