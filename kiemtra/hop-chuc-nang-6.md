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

## Ca vượt chỗ đăng ký — đã kiểm thử ngày 25/10/2026

Đã nối kiểm tra `mon_hoc.so_cho` vào API và form thêm/cập nhật giỏ. Thêm giỏ không giữ chỗ; transaction gửi kế hoạch kiểm tra lại bằng UPDATE có điều kiện và rowCount. CSDL thử được tạo từ schema/seed hiện tại sau khi đồng bộ commit `842b1f3`, tách khỏi CSDL local thật và xóa sau khi thử.

| Ca thực tế | Kết quả |
| --- | --- |
| LOG401 còn 1 chỗ; POST JSON `{"id":30,"sl":2}` có CSRF hợp lệ, cả khách và tài khoản | 422; Content-Type JSON; `{"loi":"Môn học không còn đủ chỗ đăng ký."}` |
| SEC401 còn 0 chỗ; POST id=25, sl=1 | 422, cùng thông báo thiếu chỗ |
| GET giỏ trước/sau lỗi; so sánh dữ liệu MySQL | Giỏ, số chỗ, kế hoạch và chi tiết giữ nguyên |
| Giỏ đã có LOG401 số lượng 1; gửi lại số lượng 2 | 422, vẫn giữ số lượng 1 |
| Form POST thêm môn hết chỗ hoặc cập nhật số lượng vượt chỗ, không dùng fetch | Chuyển hướng về trang, không thay giỏ |
| Hai môn; môn thứ hai hết chỗ sau khi đã thêm giỏ | Gửi kế hoạch trả 303 về giỏ, thông báo thiếu chỗ; rollback cả kế hoạch, chi tiết, số chỗ môn đầu; giữ giỏ |
| Hai môn còn đủ chỗ | Gửi thành công, trừ đúng chỗ, xóa các dòng giỏ đã gửi; snapshot tên/tín chỉ giữ nguyên khi môn thay đổi |
| Hai tiến trình cùng gửi môn chỉ còn 1 chỗ | Chỉ một thành công, một thiếu chỗ; chỉ thêm một kế hoạch; số chỗ bằng 0 |
| F5 trang chi tiết sau khi gửi thành công | Không sinh thêm kế hoạch; PRG 303 |
| POST `_csrf[]` tới gửi kế hoạch và quản trị trạng thái | Chuyển hướng 303, không TypeError; trang đơn hàng chạy được |
| Log PHP trong lượt HTTP bình thường | Rỗng, không Warning/Notice/Fatal |
| Migration trên cấu trúc cũ và chạy lại | Thêm cột với mặc định 50, giữ mọi dữ liệu cũ; lần sau giữ số chỗ hiện tại |

Chạy lại kiểm thử CSDL độc lập: `php kiemtra/kiem-thu-so-cho.php` (cần quyền tạo/xóa CSDL). File này tự tạo database ngẫu nhiên và dọn trong finally. Ca 12 không dùng `sl=6` để thay cho vượt chỗ.

MySQL local thật đã sao lưu và bổ sung cột bằng migration, giữ nguyên tất cả dữ liệu cũ. Các môn local khởi tạo 50 chỗ; mẫu 0/1 chỗ chỉ áp dụng khi nạp seed vào CSDL thử/cài mới. Đổi trạng thái không tự hoàn chỗ. Chưa chụp thêm ảnh Network 422 trong lần kiểm thử này.

## Minh chứng đang có

- [Tìm kiếm và debounce một request](hop-bai6-tim-kiem-debounce-mot-request.png).
- [Thêm giỏ bằng fetch](hop-bai6-them-gio-bang-fetch.jpg).
- [API trả lỗi 404 và JSON](hop-bai6-api-loi-404-json.jpg).
- [Thêm giỏ khi tắt JavaScript](hop-bai6-them-gio-khi-tat-javascript.jpg).
- [W3C không lỗi](hop-bai6-w3c-khong-loi.jpg).

Ảnh W3C chỉ chứng minh trang/HTML đã được đưa vào kiểm tra tại thời điểm chụp; không suy ra toàn bộ website hiện tại đạt W3C. Ảnh debounce đã có trong kho, chưa đo lại số request cho tất cả chuỗi gõ trên bản hiện tại. Chưa đo lại Lighthouse. Cần bổ sung ảnh Network 422 cho ca vượt chỗ đã kiểm thử ở trên.

Các lần kiểm thử ghi dữ liệu dùng tài khoản/kế hoạch tạm đã được dọn; kiểm tra schema/migration dùng CSDL thử riêng đã xóa. Không dùng bản ghi cá nhân hiện có để thử thao tác xóa/sửa.
