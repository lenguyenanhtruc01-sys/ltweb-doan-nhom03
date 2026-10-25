# Đăng ký qua lớp dữ liệu và chú thích đầu tệp — 25/10/2026

SQL đăng ký đã chuyển từ `dang-ky.php` sang `src/Data/KhoNguoiDung.php`, nhận PDO qua constructor. Trang giữ kiểm tra đầu vào, băm mật khẩu, xử lý lỗi trùng và PRG; lớp dữ liệu dùng prepare/execute, cố định vai trò `khach_hang` và trả ID mới. Lượt sửa này không chuyển các SQL khác của nhóm.

Kiểm thử trên bản sao/CSDL ngẫu nhiên riêng, server PHP tạm 8097; đã dừng server và xóa CSDL sau khi thử. Không thêm tài khoản vào MySQL thật.

| Ca | Kết quả |
| --- | --- |
| GET form đăng ký | 200, không Warning/Notice/Fatal |
| Form rỗng, mật khẩu 7 ký tự, xác nhận khác, email sai | Hiện lỗi, không thêm tài khoản |
| Email/tên đăng nhập/mật khẩu dạng mảng | Hiện lỗi, không TypeError hoặc Warning, không thêm tài khoản |
| Tên hiển thị có thẻ script trong form lỗi | Escape HTML |
| Đăng ký hợp lệ kèm `vai_tro=quan_tri` | 303 tới đăng nhập; vai trò vẫn là khách hàng |
| Mật khẩu đã lưu | Là hash; password_verify đúng với mật khẩu thử |
| F5 trang đăng nhập sau thành công | Không tạo tài khoản trùng |
| Email trùng, khác hoa/thường | Bắt 1062, thông báo email đã đăng ký |
| Tên đăng nhập trùng | Bắt 1062, thông báo tên đã sử dụng |
| Tên chứa `' OR 1=1 --` | Lưu như văn bản bằng tham số, không làm đổi truy vấn |
| Gọi lớp dữ liệu trực tiếp | Trả ID mới đúng, lỗi UNIQUE vẫn truyền ra dưới dạng PDOException 1062 |
| Log PHP của lượt thử | Rỗng |

Đã kiểm tra 61 tệp PHP ngoài vendor: cú pháp hợp lệ, chú thích đầu tệp có 3–5 dòng nội dung và cách thử. Đối chiếu token PHP với HEAD trước khi sửa xác nhận 22 tệp ngoài trang đăng ký chỉ thay chú thích/khoảng trắng. Đây không phải kết luận toàn bộ chức năng nhóm đã đạt.
