-- Thay MAT_KHAU_THAY_THE bằng mật khẩu mạnh riêng trước khi chạy.
-- Chạy bằng tài khoản quản trị sau khi CSDL ltweb_nhom03 đã được tạo.
-- Nếu tài khoản đã tồn tại, kiểm tra và thu hồi quyền cũ trước; CREATE USER sẽ báo lỗi.
CREATE USER 'nhom03_app'@'127.0.0.1' IDENTIFIED BY 'MAT_KHAU_THAY_THE';

GRANT SELECT, INSERT, UPDATE, DELETE
ON `ltweb_nhom03`.*
TO 'nhom03_app'@'127.0.0.1';

SHOW GRANTS FOR 'nhom03_app'@'127.0.0.1';
