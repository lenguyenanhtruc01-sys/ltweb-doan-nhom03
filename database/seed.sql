-- database/seed.sql
-- Dữ liệu mẫu (chạy SAU schema.sql)
SET NAMES utf8mb4;
USE ltweb_nhom03;

-- ============================================
-- 1. TÀI KHOẢN MẪU (Mật khẩu của cả 4 tài khoản đều là: 123456)
-- ============================================
INSERT INTO nguoi_dung (id, ten_dang_nhap, email, mat_khau, ho_ten, vai_tro) VALUES
(1, 'admin', 'admin@edugpa.local', '$2y$10$GX8yjPude4OUS2oJZ.tYnOI/rHzXKYYzccZ5CPtWZEvotsoUPsFCG', 'Quản trị viên', 'quan_tri'),
(2, 'truc', 'truc@edugpa.local', '$2y$10$GX8yjPude4OUS2oJZ.tYnOI/rHzXKYYzccZ5CPtWZEvotsoUPsFCG', 'Lê Nguyễn Anh Trúc', 'khach_hang'),
(3, 'duy', 'duy@edugpa.local', '$2y$10$GX8yjPude4OUS2oJZ.tYnOI/rHzXKYYzccZ5CPtWZEvotsoUPsFCG', 'Dương Bảo Duy', 'khach_hang'),
(4, 'uyen', 'uyen@edugpa.local', '$2y$10$GX8yjPude4OUS2oJZ.tYnOI/rHzXKYYzccZ5CPtWZEvotsoUPsFCG', 'Huỳnh Phương Uyên', 'khach_hang');

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