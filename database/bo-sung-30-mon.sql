-- Bo sung 30 mon tu seed cua Duy (origin/main 4b315cb).
-- Import vao ltweb_nhom03 hien co; khong xoa/sua mon, tai khoan hay du lieu ca nhan.
-- Chay lai khong them trung: doi chieu danh muc theo slug, mon theo ma_mon.
SET NAMES utf8mb4;
USE ltweb_nhom03;

CREATE TEMPORARY TABLE seed_danh_muc_bo_sung (
    id INT UNSIGNED PRIMARY KEY,
    ten VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TEMPORARY TABLE seed_mon_hoc_bo_sung (
    id INT UNSIGNED PRIMARY KEY,
    danh_muc_id INT UNSIGNED NOT NULL,
    ma_mon VARCHAR(20) NOT NULL,
    ten_mon VARCHAR(200) NOT NULL,
    so_tin_chi INT UNSIGNED NOT NULL,
    mo_ta TEXT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO seed_danh_muc_bo_sung (id, ten, slug) VALUES
(1, 'Môn Đại cương', 'mon-dai-cuong'),
(2, 'Môn Cơ sở ngành', 'mon-co-so-nganh'),
(3, 'Môn Chuyên ngành', 'mon-chuyen-nganh'),
(4, 'Môn Tự chọn', 'mon-tu-chon'),
(5, 'Môn đang phát triển', 'mon-dang-phat-trien');
INSERT INTO seed_mon_hoc_bo_sung (id, danh_muc_id, ma_mon, ten_mon, so_tin_chi, mo_ta) VALUES
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

START TRANSACTION;
INSERT INTO danh_muc (ten, slug)
SELECT s.ten, s.slug FROM seed_danh_muc_bo_sung s
WHERE NOT EXISTS (SELECT 1 FROM danh_muc d WHERE d.slug = s.slug)
ORDER BY s.id;

-- Khong ep ID cua seed len ID local, tranh ghi de va hong lien ket hien co.
INSERT INTO mon_hoc (danh_muc_id, ma_mon, ten_mon, so_tin_chi, mo_ta)
SELECT d.id, s.ma_mon, s.ten_mon, s.so_tin_chi, s.mo_ta
FROM seed_mon_hoc_bo_sung s
JOIN seed_danh_muc_bo_sung sd ON sd.id = s.danh_muc_id
JOIN danh_muc d ON d.slug = sd.slug
WHERE NOT EXISTS (SELECT 1 FROM mon_hoc m WHERE m.ma_mon = s.ma_mon)
ORDER BY s.id;
COMMIT;

DROP TEMPORARY TABLE seed_mon_hoc_bo_sung;
DROP TEMPORARY TABLE seed_danh_muc_bo_sung;
SELECT COUNT(*) AS tong_so_mon FROM mon_hoc;

