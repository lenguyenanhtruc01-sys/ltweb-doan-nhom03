# Chức năng 6 — kiểm tra local ngày 25/10/2026

Môi trường: PHP 8.2, MySQL/MariaDB local `ltweb_nhom03`, PHP server tạm 8097; endpoint lỗi CSDL được thử bằng tiến trình PHP khác ở 8098 với DB_PORT=1. Không dừng/chỉnh cấu hình MySQL thật.

| Ca | Kết quả thực tế |
| --- | --- |
| GET tìm Web / chi tiết id=1 | 200, JSON, danh sách Web có 1 môn |
| GET id=abc / id không có | 400 / 404 |
| POST JSON hỏng | 400 |
| POST sl=-3, sl=6, id dạng mảng | 422, giỏ không đổi |
| POST môn không tồn tại với token đúng | 404 |
| DELETE giỏ / POST sản phẩm | 405, Allow lần lượt GET, POST / GET |
| q[] | 422, không Warning |
| POST thiếu token phiên (request độc lập) | 401 |
| GET giỏ, POST hợp lệ id=1 sl=2, đọc lại | 200, tổng số lượng 2 |
| CSDL không kết nối được: cả hai endpoint | 500, đúng JSON `{"loi":"Lỗi máy chủ"}` |
| HTML form POST không dùng fetch | Redirect về danh sách, thêm thành công; GET API cùng session thấy 1 môn |
| Trình duyệt: nhập Web | Kết quả đổi từ 8 xuống 1; URL giữ bộ lọc; không bấm submit |
| Trình duyệt: thêm Web từ kết quả động | Header 0 → 1; nút đã thêm; giữ trang danh sách |
| Trình duyệt: thêm id=2 tại chi tiết | Header 1 → 2; nút đã thêm; giữ trang chi tiết |
| Cú pháp PHP, hai file JS, git diff --check | Đạt |

Ảnh bằng chứng: `hop-api-them-ke-hoach.png` (nút đã thêm tại chi tiết). Chưa đo W3C/Lighthouse, chưa ghi số request debounce bằng Network, chưa thử CSRF lỗi trên tài khoản thật ngoài các request kiểm thử phiên độc lập. Luồng không JS kiểm tra bằng HTTP form POST/GET; chưa tắt JavaScript trong thiết lập trình duyệt.

Các lượt thêm giỏ chỉ ở session kiểm thử, không thêm phiếu bầu/tài khoản hay sửa dữ liệu môn học.
