# Chức năng 6 — kiểm tra local ngày 25/10/2026

Môi trường: PHP 8.2, MySQL/MariaDB local `ltweb_nhom03`, PHP server tạm 8097. Code đối chiếu: `a22ad52`. Dữ liệu hiện tại có 30 môn; tìm từ khóa Web trả 3 môn.

## Kết quả đã kiểm tra lại

| Ca | Kết quả thực tế |
| --- | --- |
| Trình duyệt: nhập Web | Tìm được 3 môn; giữ bộ lọc trên URL; không bấm submit, không tải lại trang |
| Phân trang bằng fetch | Chuyển trang 2 thành công, không phát sinh điều hướng tải lại trang |
| Thêm môn từ danh sách ban đầu | API 200; header 0 → 1, nút đổi thành đã thêm, không tải lại trang |
| Thêm môn từ kết quả tìm kiếm động | API 200; header 1 → 2, vẫn ở danh sách |
| Thêm tại chi tiết id=2 | API 200; header 2 → 3, vẫn ở trang chi tiết |
| Tắt JavaScript trong trình duyệt | Form tìm kiếm GET và form thêm giỏ POST vẫn chạy; thêm xong redirect về danh sách |
| Giỏ của một tài khoản trên hai phiên | A đăng xuất sau khi B thêm môn; giỏ vẫn giữ cả hai môn |
| Sửa/xóa giỏ từ phiên cũ | Không ghi đè số lượng môn khác; không khôi phục môn đã bị xóa ở phiên còn lại |
| Đăng nhập bằng tên hoặc email | Khôi phục giỏ/yêu thích từ MySQL; hai tài khoản không lẫn dữ liệu |
| Yêu thích cũ có mã môn không tồn tại | Bỏ qua mã lỗi 404, giữ môn hợp lệ; đăng xuất được |
| API yêu thích lỗi 500, mất mạng hoặc quá thời gian | Không chặn đăng xuất; danh sách cũ chưa chuyển xong được giữ để thử lại |
| Gửi `_csrf[]`, mảng lồng, token rỗng/thiếu/sai ở trang kế hoạch | Redirect 303, không HTTP 500; giỏ và trạng thái không đổi |
| Token hợp lệ tại trang kế hoạch | Tạo kế hoạch và quản trị đổi trạng thái thành công; khách bị chặn đổi trạng thái bằng 403 |
| Cú pháp PHP/JavaScript và kiểm tra diff | Đạt; tệp CRLF được kiểm tra với `git -c core.whitespace=cr-at-eol diff --check` |

## Các ca API đã ghi nhận ở lần kiểm tra trước

Các kết quả dưới đây là lịch sử kiểm thử trên môi trường local; chưa chạy lại toàn bộ các mã lỗi này trong lần cập nhật biên bản trên bản `a22ad52`.

| Ca | Kết quả đã ghi nhận |
| --- | --- |
| GET tìm kiếm / chi tiết id=1 | 200, JSON |
| GET id=abc / id không có | 400 / 404 |
| POST JSON hỏng | 400 |
| POST sl=-3, sl=6, id dạng mảng | 422, giỏ không đổi |
| POST môn không tồn tại với token đúng | 404 |
| DELETE giỏ / POST sản phẩm | 405; Allow lần lượt GET, POST / GET |
| q[] | 422, không Warning |
| POST thiếu token phiên | 401 |
| GET giỏ, POST hợp lệ id=1 sl=2, đọc lại cùng phiên | 200, tổng số lượng 2 |
| CSDL không kết nối được: cả hai endpoint | 500, JSON `{"loi":"Lỗi máy chủ"}` |

Ca mất kết nối CSDL đã thử bằng tiến trình PHP riêng tại cổng 8098 với `DB_PORT=1`, không dừng hay chỉnh cấu hình MySQL thật. Kết quả tìm Web có 1 môn ở biên bản cũ thuộc bộ dữ liệu 8 môn, đã được thay bằng kết quả hiện tại ở bảng trên.

## Ca vượt chỗ đăng ký — CHỜ TRIỂN KHAI, chưa đạt để chốt báo cáo

Tiền điều kiện: nhóm bổ sung số chỗ còn lại/trạng thái mở đăng ký và kiểm tra giới hạn đó trong API, dùng chung quy tắc với giao dịch gửi kế hoạch. Hiện schema chưa có dữ liệu số chỗ; giới hạn `sl = 1–5` chỉ là giới hạn số lượng của giỏ.

| Bước | Thao tác / kết quả cần kiểm tra |
| --- | --- |
| Chuẩn bị | Dùng môn có thật còn 1 chỗ; dùng phiên kiểm thử và CSRF token hợp lệ |
| Ghi nhận ban đầu | GET giỏ, lưu danh sách môn và số lượng hiện có |
| Gửi yêu cầu | POST JSON `{"id":<id_môn_thử>,"sl":2}` tới `api/gio-hang.php`; số lượng 2 vẫn trong khoảng 1–5 |
| Kết quả mong đợi | HTTP 422; Content-Type JSON; nội dung báo không đủ chỗ, không có HTML/Warning |
| Kiểm tra sau lỗi | GET lại cùng giỏ: danh sách và số lượng giữ nguyên; không tạo kế hoạch hoặc trừ chỗ |
| Ghi minh chứng | Chụp Network có Payload, HTTP 422 và JSON; điền kết quả thực tế sau khi chạy |

Trạng thái hiện tại: **chưa thể thực hiện đúng tiền điều kiện, chưa xác nhận đạt**. Ca `sl=6` không thay thế ca vượt chỗ. Ca rollback khi môn thứ hai không đủ chỗ thuộc giao dịch chức năng 5 và cần phối hợp kiểm thử riêng.

## Minh chứng đang có

- [Tìm kiếm và debounce một request](hop-bai6-tim-kiem-debounce-mot-request.png).
- [Thêm giỏ bằng fetch](hop-bai6-them-gio-bang-fetch.jpg).
- [API trả lỗi 404 và JSON](hop-bai6-api-loi-404-json.jpg).
- [Thêm giỏ khi tắt JavaScript](hop-bai6-them-gio-khi-tat-javascript.jpg).
- [W3C không lỗi](hop-bai6-w3c-khong-loi.jpg).

Ảnh W3C chỉ chứng minh trang/HTML đã được đưa vào kiểm tra tại thời điểm chụp; không suy ra toàn bộ website hiện tại đạt W3C. Ảnh debounce đã có trong kho, chưa đo lại số request cho tất cả chuỗi gõ trên bản hiện tại. Chưa đo lại Lighthouse. Cần chụp thêm kết quả vượt chỗ sau khi có nghiệp vụ tương ứng.

Các lần kiểm thử ghi dữ liệu dùng tài khoản/kế hoạch tạm đã được dọn; kiểm tra schema/migration dùng CSDL thử riêng đã xóa. Không dùng bản ghi cá nhân hiện có để thử thao tác xóa/sửa.
