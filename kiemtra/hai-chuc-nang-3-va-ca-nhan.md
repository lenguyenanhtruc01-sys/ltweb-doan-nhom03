# Chức năng 3 và trang cá nhân Hải — kiểm thử ngày 25/10/2026

Môi trường: PHP 8.2, PDO MySQL/MariaDB; server PHP tạm 8097. Dữ liệu kiểm thử dùng database ngẫu nhiên từ schema/seed, được xóa sau khi chạy. Không sửa hoặc xóa môn/kế hoạch thật để thử CRUD.

## Bảng 3 — CRUD môn học

| Ca | Kết quả thực tế |
| --- | --- |
| Khách mở hoặc POST trang quản lý/xóa | 302 tới đăng nhập, không đổi môn |
| Tài khoản `an` mở hoặc POST quản lý/xóa | 403, không đổi môn |
| Admin mở danh sách | 10 môn/trang, chuyển trang 2 được |
| Mã dạng mảng, tín chỉ 0, số chỗ âm, danh mục giả | 422, hiện lỗi, CSDL không đổi |
| Thêm mã MAT101 đã có | Bắt 1062, thông báo mã trùng, không sinh bản ghi |
| Ảnh giả chứa mã PHP mang tên PNG | Từ chối, không thêm môn |
| PNG thật mang tên `.php` | Lưu tên ngẫu nhiên `.png`, không lưu phần mở rộng do trình duyệt gửi |
| Thêm môn hợp lệ, tên có dấu nháy và thẻ script | 303; lưu nguyên dữ liệu, HTML escape, không chạy script |
| Sửa môn vừa thêm | Form điền sẵn; cập nhật tên/số chỗ; không chọn ảnh mới thì giữ ảnh cũ; 303 |
| F5 sau thêm thành công | Không tạo bản ghi trùng |
| Mở liên kết xác nhận xóa | Chỉ hiển thị form hỏi lại, chưa xóa dữ liệu |
| GET xoa-mon-hoc.php?id=... | 405, Allow: POST; không đổi dữ liệu |
| POST xóa `_csrf[]` | 303 và báo phiên không hợp lệ; không TypeError, không xóa |
| POST xóa môn thử chưa có kế hoạch | Xóa thành công, 303 |
| POST xóa MAT101 đã có kế hoạch | Bắt 1451, thông báo đang sử dụng; môn và chi tiết giữ nguyên |

## Bảng 5 — dữ liệu cá nhân

| Ca | Kết quả thực tế |
| --- | --- |
| Schema/seed mới | `tv_luot_xem` có 7 ngày; `tv_anh_dai_dien` có dòng Hải; liên kết chủ trang tới `hai` |
| Khách, `an`, admin xem trang Hải | Xem được, không có form đổi avatar |
| Các tài khoản trên cố POST upload | 403, metadata avatar không đổi |
| Chủ trang `hai`, token thiếu/dạng mảng | 303, không đổi ảnh |
| Chủ trang tải ảnh giả | Báo lỗi qua session một lần, không đổi avatar |
| Chủ trang tải PNG thật tên `.php` | 303, tên tệp `.png` lưu MySQL; tệp nằm trong uploads |
| JSON avatar cũ bị sai trong bản sao | Trang vẫn chạy; không đọc/ghi JSON trong luồng trang cá nhân |
| F5 sau đổi ảnh | Giữ ảnh, không upload lại; thông báo chỉ xuất hiện một lần |
| Có thêm 1.000 lượt xem của MSSV khác cùng ngày | Thống kê Hải vẫn chỉ tính Hải, đủ 7 ngày |
| Migration chạy hai lần | Giữ mật khẩu, không tạo trùng tài khoản, không thay chủ đã có, không ghi đè avatar mới |
| Migration có JSON cũ hợp lệ | Nhập tên ảnh cũ vào MySQL khi chưa có avatar |

## HTML và bố cục

- Nu HTML Checker local 26.10.12: **0 lỗi** cho danh sách CRUD, sửa môn, xác nhận xóa, trang Hải khi khách và chủ trang xem. Chưa gửi lại lên dịch vụ W3C trực tuyến.
- Trình duyệt 360px: hai trang quản lý môn và cá nhân Hải không cuộn ngang toàn trang; CSS/JS tải 200.
- Log PHP trong các ca bình thường và dữ liệu sai nêu trên: rỗng. Không suy ra các trang khác của nhóm đã đạt.
- Chưa chụp bộ ảnh minh chứng mới cho các ca trên.

## Cách kiểm tra tay

1. Với CSDL cũ, sao lưu, chạy migration số chỗ/FK theo README rồi chạy `php database/nang-cap-ca-nhan-hai.php`; không import schema có DROP DATABASE vào CSDL thật.
2. Đăng nhập `admin`, vào **Quản lý môn học**. Thử thêm một mã mới, sửa, kiểm tra phân trang và xác nhận xóa. Thử mã MAT101 trùng và xóa MAT101 đang được dùng.
3. Đăng xuất, mở trang Hải: không thấy form ảnh; POST trực tiếp bị 403. Đăng nhập `hai / matkhau123` (tài khoản mẫu cài mới hoặc do migration tạo) thì có form đổi ảnh.
4. Tải ảnh PNG/JPG/WebP thật dưới 2MB, kiểm tra thông báo, F5, tên ảnh trong `tv_anh_dai_dien`. Mật khẩu tài khoản đã tồn tại được migration giữ nguyên.
5. Seed 7 ngày chỉ dành cho cài mới/CSDL thử. Migration local không thêm lượt xem mẫu vào dữ liệu thật.

Ảnh cũ và JSON cũ đã được sao lưu ngoài kho trước khi áp dụng migration local. Runtime chỉ đọc tên avatar từ MySQL; có thể giữ JSON cũ làm bản lưu, không cần tệp này để vận hành.
