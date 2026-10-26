-- database/seed.sql
-- Mật khẩu của cả 5 tài khoản: matkhau123
SET NAMES utf8mb4;
USE ltweb_nhom03;

-- ============================================
-- 1. TÀI KHOẢN MẪU
-- ============================================
INSERT INTO nguoi_dung (id, email, ten_dang_nhap, mat_khau, ho_ten, vai_tro) VALUES
(1, 'admin@example.com', 'admin', '$2y$12$qHb8qF.xgsQsDEN5nvxo6OsTDLDQmDQmTnjDdlNCp7MqAzd4fkWBG', 'Quản trị viên', 'quan_tri'),
(2, 'hop@example.com', 'hop', '$2y$12$u.0T5HsnlVJWcPLCbRUsIO9EXccEweLCYOD7R46J.TJ9bH4ju5eGC', 'Phan Văn Hợp', 'khach_hang'),
(3, 'uyen@example.com', 'uyen', '$2y$12$3ZoP8Zw46/1SUlmMu/unR.ZIvgTjCuo2NGWs2F9ttQBE.FmFo83bO', 'Huỳnh Phương Uyên', 'khach_hang'),
(4, 'hai@example.com', 'hai', '$2y$12$qHb8qF.xgsQsDEN5nvxo6OsTDLDQmDQmTnjDdlNCp7MqAzd4fkWBG', 'Lê Dương Hoàng Hải', 'khach_hang'),
(5, 'duy@example.com', 'duy', '$2y$12$qHb8qF.xgsQsDEN5nvxo6OsTDLDQmDQmTnjDdlNCp7MqAzd4fkWBG', 'Dương Bảo Duy', 'khach_hang');

-- ============================================
-- 2. DANH MỤC (5 danh mục, mục 5 rỗng theo yêu cầu đề)
-- ============================================
INSERT INTO danh_muc (id, ten, slug) VALUES
(1, 'Môn Đại cương', 'mon-dai-cuong'),
(2, 'Môn Cơ sở ngành', 'mon-co-so-nganh'),
(3, 'Môn Chuyên ngành', 'mon-chuyen-nganh'),
(4, 'Môn Tự chọn', 'mon-tu-chon'),
(5, 'Môn đang phát triển', 'mon-dang-phat-trien');

-- ============================================
-- 3. MÔN HỌC — 30 MÔN (đủ yêu cầu đề)
-- ============================================
INSERT INTO mon_hoc (id, danh_muc_id, ma_mon, ten_mon, so_tin_chi, mo_ta) VALUES
-- Danh mục 1: Đại cương (7 môn)
( 1, 1, 'MAT101', 'Giải tích 1', 4, 'Môn toán cơ bản cho sinh viên năm nhất'),
( 2, 1, 'MAT102', 'Đại số tuyến tính', 3, 'Ma trận, hệ phương trình, không gian vector'),
( 9, 1, 'MAT201', 'Xác suất thống kê', 3, 'Xác suất, biến ngẫu nhiên, thống kê mô tả'),
(10, 1, 'MAT301', 'Phương trình vi phân', 3, 'Phương trình vi phân cấp 1, cấp 2'),
(11, 1, 'PHY101', 'Vật lý đại cương', 4, 'Cơ học, nhiệt học, điện từ học'),
(12, 1, 'POL101', 'Triết học Mác - Lênin', 3, 'Chủ nghĩa duy vật biện chứng và lịch sử'),
(13, 1, 'ENG101', 'Tiếng Anh tổng quát 1', 3, 'Kỹ năng nghe, nói, đọc, viết A2'),

-- Danh mục 2: Cơ sở ngành (8 môn)
( 3, 2, 'INT101', 'Lập trình cơ bản', 3, 'Giới thiệu về lập trình với C/C++'),
( 4, 2, 'INT102', 'Cấu trúc dữ liệu và giải thuật', 4, 'Mảng, danh sách liên kết, cây, đồ thị'),
(14, 2, 'INT201', 'Lập trình hướng đối tượng', 3, 'Lớp, kế thừa, đa hình, đóng gói'),
(15, 2, 'INT202', 'Kiến trúc máy tính', 3, 'CPU, bộ nhớ, bus, hợp ngữ cơ bản'),
(16, 2, 'INT301', 'Hệ điều hành', 3, 'Quản lý tiến trình, bộ nhớ, file system'),
(17, 2, 'INT303', 'Mạng máy tính', 3, 'OSI, TCP/IP, định tuyến, bảo mật mạng'),
(18, 2, 'INT304', 'Kỹ thuật lập trình C++ ''nâng cao''', 3, 'Template, STL — tên có dấu nháy để test escape'),
(19, 2, 'INT305', 'Toán rời rạc', 3, 'Logic mệnh đề, tập hợp, quan hệ, đồ thị'),

-- Danh mục 3: Chuyên ngành (9 môn)
( 5, 3, 'WEB301', 'Thiết kế và lập trình Web', 3, 'HTML, CSS, JavaScript, PHP cơ bản'),
( 6, 3, 'DB301', 'Cơ sở dữ liệu', 3, 'Mô hình quan hệ, SQL, chuẩn hóa'),
(20, 3, 'WEB302', 'Lập trình Frontend nâng cao', 3, 'React/Vue, component, state management'),
(21, 3, 'WEB401', 'Bảo mật ứng dụng Web', 3, 'OWASP Top 10, XSS, SQL Injection, CSRF'),
(22, 3, 'DB302', 'Hệ quản trị cơ sở dữ liệu', 3, 'Tối ưu truy vấn, index, transaction'),
(23, 3, 'MOB401', 'Lập trình di động', 3, 'Android với Kotlin, iOS với Swift'),
(24, 3, 'AI402', 'Học máy', 3, 'Hồi quy, phân loại, mạng nơ-ron cơ bản'),
(25, 3, 'SEC401', 'An toàn thông tin', 3, 'Mã hoá, xác thực. Môn đã đóng đăng ký trong HK1 2026-2027.'),
(26, 3, 'DEVOPS01', 'DevOps và triển khai', 3, 'Docker, CI/CD, giám sát hệ thống'),

-- Danh mục 4: Tự chọn (6 môn)
( 7, 4, 'ENG201', 'Tiếng Anh chuyên ngành', 2, 'Tiếng Anh cho công nghệ thông tin'),
( 8, 4, 'AI401', 'Nhập môn Trí tuệ nhân tạo', 3, 'Tổng quan về AI và học máy'),
(27, 4, 'ENG202', 'Tiếng Anh giao tiếp', 2, 'Luyện nghe nói theo chủ đề CNTT'),
(28, 4, 'SOFT201', 'Kỹ năng mềm', 2, 'Giao tiếp, làm việc nhóm, thuyết trình'),
(29, 4, 'START01', 'Khởi nghiệp công nghệ', 2, 'Ý tưởng, mô hình kinh doanh, gọi vốn'),
(30, 4, 'LOG401', 'Tư duy logic và giải quyết vấn đề', 2, 'Phương pháp luận, tư duy phản biện');

-- Mẫu kiểm thử: SEC401 hết chỗ; LOG401 còn 1 chỗ (POST sl=2 phải trả 422).
-- Các số này là số chỗ còn lại; kế hoạch mẫu bên dưới đã được tính từ trước.
UPDATE mon_hoc SET so_cho = 0 WHERE ma_mon = 'SEC401';
UPDATE mon_hoc SET so_cho = 1 WHERE ma_mon = 'LOG401';

-- ============================================
-- 3a. ẢNH MÔN HỌC (tên file trong thư mục images/)
-- ENG101 không có ảnh riêng nên để anh = NULL.
-- ============================================
UPDATE mon_hoc
SET anh = CASE ma_mon
    WHEN 'AI401' THEN 'AI401.png'
    WHEN 'AI402' THEN 'AI402.png'
    WHEN 'DB301' THEN 'DB301.png'
    WHEN 'DB302' THEN 'DB302.png'
    WHEN 'DEVOPS01' THEN 'DEVOPS01.png'
    WHEN 'ENG201' THEN 'ENG201.png'
    WHEN 'ENG202' THEN 'ENG202.png'
    WHEN 'INT101' THEN 'INT101.png'
    WHEN 'INT102' THEN 'INT102.png'
    WHEN 'INT201' THEN 'INT201.png'
    WHEN 'INT202' THEN 'INT202.png'
    WHEN 'INT301' THEN 'INT301.png'
    WHEN 'INT303' THEN 'INT303.png'
    WHEN 'INT304' THEN 'INT304.png'
    WHEN 'INT305' THEN 'INT305.png'
    WHEN 'LOG401' THEN 'LOG401.png'
    WHEN 'MAT101' THEN 'MAT101.png'
    WHEN 'MAT102' THEN 'MAT102.png'
    WHEN 'MAT201' THEN 'MAT201.png'
    WHEN 'MAT301' THEN 'MAT301.png'
    WHEN 'MOB401' THEN 'MOB401.png'
    WHEN 'PHY101' THEN 'PHY101.png'
    WHEN 'POL101' THEN 'POL101.png'
    WHEN 'SEC401' THEN 'SEC401.png'
    WHEN 'SOFT201' THEN 'SOFT201.png'
    WHEN 'START01' THEN 'START01.png'
    WHEN 'WEB301' THEN 'WEB301.png'
    WHEN 'WEB302' THEN 'WEB302.png'
    WHEN 'WEB401' THEN 'WEB401.png'
    ELSE NULL
END;

-- ============================================
-- 3b. KẾ HOẠCH HỌC TẬP MẪU
-- ============================================
INSERT INTO ke_hoach_hoc_tap (id, nguoi_dung_id, trang_thai, ghi_chu) VALUES
(1, 2, 'cho_duyet', 'Kế hoạch học kỳ 1 năm 3'),
(2, 3, 'da_duyet',  'Kế hoạch học kỳ 2 năm 2'),
(3, 4, 'cho_duyet', 'Kế hoạch mẫu của Hải');

INSERT INTO chi_tiet_ke_hoach
    (ke_hoach_id, mon_hoc_id, ma_mon, ten_mon, so_tin_chi, so_luong) VALUES
(1, 1, 'MAT101', 'Giải tích 1', 4, 1),
(1, 3, 'INT101', 'Lập trình cơ bản', 3, 1),
(2, 5, 'WEB301', 'Thiết kế và lập trình Web', 3, 1),
(2, 6, 'DB301', 'Cơ sở dữ liệu', 3, 1),
(3, 17, 'INT303', 'Mạng máy tính', 3, 1);

-- ============================================
-- 4. THÀNH VIÊN & DỮ LIỆU PHẦN B
-- ============================================
INSERT INTO thanh_vien (mssv, ho_ten) VALUES
('3120224034', 'Dương Bảo Duy'),
('3120224159', 'Lê Nguyễn Anh Trúc');

INSERT INTO thanh_vien (mssv, ho_ten)
SELECT '3120224064', 'Phan Văn Hợp'
WHERE NOT EXISTS (SELECT 1 FROM thanh_vien WHERE mssv = '3120224064');

INSERT INTO thanh_vien (mssv, ho_ten)
SELECT '3120224042', 'Lê Dương Hoàng Hải'
WHERE NOT EXISTS (SELECT 1 FROM thanh_vien WHERE mssv = '3120224042');

UPDATE thanh_vien SET nguoi_dung_id=4 WHERE mssv='3120224042';
UPDATE thanh_vien SET nguoi_dung_id=5 WHERE mssv='3120224034';
INSERT INTO tv_anh_dai_dien (mssv,ten_tep) VALUES ('3120224042',NULL);
INSERT INTO tv_luot_xem (mssv,ngay,so_luot) VALUES
('3120224042',CURRENT_DATE,3),
('3120224042',CURRENT_DATE - INTERVAL 1 DAY,2),
('3120224042',CURRENT_DATE - INTERVAL 2 DAY,4),
('3120224042',CURRENT_DATE - INTERVAL 3 DAY,1),
('3120224042',CURRENT_DATE - INTERVAL 4 DAY,5),
('3120224042',CURRENT_DATE - INTERVAL 5 DAY,2),
('3120224042',CURRENT_DATE - INTERVAL 6 DAY,1);

INSERT INTO tv_tra_cuu_mang (mssv, muc_tieu, dia_chi_ip, loai_ip)
SELECT '3120224064', '127.0.0.1', '127.0.0.1', 'IPv4'
WHERE NOT EXISTS (SELECT 1 FROM tv_tra_cuu_mang WHERE mssv = '3120224064');

INSERT INTO tv_binh_chon (mssv, nguoi_dung_id, tuy_chon)
SELECT '3120224064', u.id, 'huu_ich' FROM nguoi_dung u
WHERE u.vai_tro = 'khach_hang'
  AND NOT EXISTS (SELECT 1 FROM tv_binh_chon b WHERE b.mssv = '3120224064' AND b.nguoi_dung_id = u.id)
ORDER BY u.id LIMIT 1;

-- ============================================
-- Phần B: Dữ liệu mẫu cho Duy (MSSV 3120224034)
-- ============================================
-- Nạp sẵn vài món ăn
INSERT INTO tv_mon_an (mssv, ten_mon, bua) VALUES
('3120224034', 'Phở bò', 'sang'),
('3120224034', 'Cơm tấm', 'trua'),
('3120224034', 'Bún chả', 'trua'),
('3120224034', 'Lẩu Thái', 'toi'),
('3120224034', 'Cơm gà', 'trua'),
('3120224034', 'Bún bò Huế', 'toi');

-- Nạp sẵn vài ván Oẳn tù tì
INSERT INTO tv_lich_su_ott (mssv, nguoi_chon, may_chon, ket_qua) VALUES
('3120224034', 'keo', 'bao', 'thang'),
('3120224034', 'bua', 'bao', 'thua'),
('3120224034', 'bao', 'bao', 'hoa');

-- ============================================
-- PHẦN B - DỮ LIỆU MẪU CỦA ANH TRÚC
-- MSSV: 3120224159
-- ============================================

INSERT INTO tv_du_an
    (mssv, ten_du_an, mo_ta, cong_nghe, nam_thuc_hien)
VALUES
(
    '3120224159',
    'EduGPA',
    'Website hỗ trợ sinh viên tra cứu môn học và lập kế hoạch học tập.',
    'HTML, CSS, JavaScript, PHP, MySQL',
    2026
),
(
    '3120224159',
    'Liên Chiểu Xanh',
    'Website hỗ trợ báo cáo vấn đề môi trường và kết nối hoạt động tình nguyện.',
    'HTML, CSS, JavaScript, PostgreSQL, Leaflet',
    2026
);
INSERT INTO thanh_vien (mssv, ho_ten)
VALUES ('3120224174', 'Huỳnh Phương Uyên')
ON DUPLICATE KEY UPDATE ho_ten = VALUES(ho_ten);

INSERT INTO tv_uyen_ky_nang (mssv, ten, nhom) VALUES
('3120224174', 'HTML5', 'web'),
('3120224174', 'Thiết kế giao diện website', 'web'),
('3120224174', 'Làm việc nhóm', 'lam-viec'),
('3120224174', 'Quản lý mã nguồn với GitHub', 'lam-viec'),
('3120224174', 'Thiết kế và quản lý cơ sở dữ liệu', 'du-lieu')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO tv_uyen_thoi_khoa_bieu
    (mssv, ma_mon, ten_mon, ma_lop, thu, tiet_bat_dau, tiet_ket_thuc, phong)
VALUES
('3120224174', '31231755', 'Thiết kế và lập trình web', '24-0102', 8, 1, 3, 'B3-303'),
('3120224174', '31231330', 'Khai phá dữ liệu', '24-0102', 5, 4, 6, 'A5-404B'),
('3120224174', '31241283', 'Hệ quản trị cơ sở dữ liệu', '24-0301', 4, 7, 10, 'B1-104'),
('3120224174', '31231016', 'Công nghệ phần mềm', '24-0103', 5, 10, 12, 'B3-304'),
('3120224174', '21221904', 'Lịch sử Đảng Cộng sản Việt Nam', '24-0318', 2, 11, 12, 'A6-502'),
('3120224174', '31221010', 'An toàn thông tin', '24-0103', 7, 11, 12, 'B3-506')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO tv_lich_su_ott (mssv,nguoi_chon,may_chon,ket_qua) VALUES
('3120224034','bua','keo','thang'),
('3120224034','bao','keo','thua');

