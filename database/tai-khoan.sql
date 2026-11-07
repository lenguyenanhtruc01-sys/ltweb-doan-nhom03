-- Thay MAT_KHAU_THAY_THE bằng mật khẩu mạnh riêng trước khi chạy.
-- Chạy bằng tài khoản quản trị sau khi CSDL ltweb_nhom03 đã được tạo.
-- Chỉ dùng nhom03_app cho website EduGPA. Kiểm tra SHOW GRANTS và dừng website
-- trước khi chạy: tài khoản cũ sẽ bị xóa rồi tạo lại, thu hồi cả quyền/role cũ.
-- Việc này chỉ xóa thông tin tài khoản/quyền MySQL, không xóa dữ liệu CSDL.
DROP USER IF EXISTS 'nhom03_app'@'127.0.0.1';

CREATE USER 'nhom03_app'@'127.0.0.1' IDENTIFIED BY 'MAT_KHAU_THAY_THE';

GRANT SELECT, INSERT, UPDATE, DELETE
ON `ltweb_nhom03`.*
TO 'nhom03_app'@'127.0.0.1';

SHOW GRANTS FOR 'nhom03_app'@'127.0.0.1';
