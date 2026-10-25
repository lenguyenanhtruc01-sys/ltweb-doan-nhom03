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

## Bổ sung: quên/đặt lại mật khẩu qua KhoNguoiDung

SQL của `quen-mat-khau.php` và `dat-lai-mat-khau.php` đã chuyển vào cùng lớp dữ liệu nhận PDO. Tạo yêu cầu và đổi mật khẩu dùng transaction; mã mới hủy mã cũ, khi đổi kiểm tra lại mã còn hiệu lực bằng UPDATE có điều kiện. Trang chỉ xử lý biểu mẫu, session, băm mật khẩu và PRG.

Kiểm thử trên CSDL tạm riêng ngày 25/10/2026, không thay mật khẩu tài khoản thật:

| Ca | Kết quả |
| --- | --- |
| Email có/không có tài khoản, sai định dạng hoặc dạng mảng | Cùng thông báo chung, không Warning/TypeError |
| Tạo yêu cầu thành công | 303 về GET; chỉ lưu SHA-256 của mã trong CSDL, hiệu lực 30 phút |
| F5 sau khi tạo yêu cầu | Không tạo mã mới; thông báo/link demo trong session chỉ hiện một lần |
| Xin mã mới | Mã cũ bị hủy, chỉ một mã còn hiệu lực |
| Mã sai, bị hủy, hết hạn, dạng mảng hoặc đã dùng | Không đổi mật khẩu |
| Mật khẩu quá ngắn, xác nhận khác hoặc dạng mảng | Báo lỗi, giữ mật khẩu và mã chưa dùng |
| Đổi thành công | 303 tới đăng nhập; hash mới đúng; xóa các phiên ghi nhớ của tài khoản |
| Hai tiến trình dùng cùng một mã | Chỉ một lần đổi thành công |
| Ép lỗi SQL ở bước thu hồi phiên ghi nhớ | Rollback mật khẩu, trạng thái mã và dữ liệu phiên |
| Log PHP | Không Warning/Notice/Deprecated/Fatal; vẫn có log thông tin yêu cầu demo như trước |

Luồng demo dev chưa gửi email SMTP. Quy tắc mật khẩu tối thiểu 6 ký tự ở trang đặt lại được giữ nguyên trong lần chuyển lớp dữ liệu này. Thu hồi phiên ở đây là cookie ghi nhớ, không khẳng định đã vô hiệu hóa mọi PHP session trên trình duyệt khác.
