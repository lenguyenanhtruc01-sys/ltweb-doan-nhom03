# EduGPA — Đồ án nhóm 03

Website PHP hỗ trợ sinh viên tra cứu môn học, lập kế hoạch học tập, gửi liên hệ và quản trị các liên hệ đã nhận.

## 1. Yêu cầu môi trường

- PHP 8.1 trở lên (đã thử nghiệm với PHP 8.2).
- Apache có PHP, ví dụ XAMPP; hoặc máy chủ tích hợp PHP.
- MySQL/MariaDB và extension PHP `pdo_mysql`.
- Tiện ích PHP `fileinfo` và `mbstring`.
- Composer (chỉ cần khi thư mục `vendor/` chưa có).

## 2. Cài đặt và chạy

1. Chép thư mục dự án vào `C:\xampp\htdocs\ltweb-doan-nhom03`.
2. Mở XAMPP Control Panel, khởi động Apache và MySQL.
3. Tại thư mục dự án, chạy `composer install`.
4. Cài mới: import `database/schema.sql`, rồi `database/seed.sql` bằng phpMyAdmin.

   **Lưu ý:** schema có `DROP DATABASE`. Sao lưu trước khi tạo lại CSDL; không import lại schema chỉ để cập nhật code.

5. Mở http://localhost/ltweb-doan-nhom03/

Kết nối chung trong `inc/db.php`: database `ltweb_nhom03`, host `127.0.0.1`, port `3306`, user `root`, mật khẩu rỗng, charset `utf8mb4`. Có thể cấu hình `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` qua môi trường PHP/Apache.

Với database cũ chưa có email, sao lưu rồi chạy `php database/migrate-email.php`; migration giữ tài khoản và dữ liệu, không thay thế toàn bộ schema.

Không mở trực tiếp tệp PHP bằng đường dẫn ổ đĩa.

Database đang dùng: import `database/luu-gio-va-yeu-thich.sql` để thêm hai bảng lưu giỏ và yêu thích theo tài khoản; không import lại schema có `DROP DATABASE`. Bản cài mới đã có hai bảng này trong schema.

### Chạy không cần Apache

```bash
composer install
php -S localhost:8000
```

Mở http://localhost:8000/index.php; MySQL vẫn cần chạy. PHP cần quyền ghi vào `storage/`, `uploads/`, `logs/`.

## 3. Tài khoản thử

Đăng nhập bằng **tên đăng nhập hoặc email**. Các tài khoản trong seed hiện tại đều dùng mật khẩu `123456`:

| Tên đăng nhập | Email | Vai trò |
| --- | --- | --- |
| `admin` | `admin@edugpa.local` | Quản trị |
| `truc` | `truc@edugpa.local` | Khách hàng |
| `duy` | `duy@edugpa.local` | Khách hàng |
| `uyen` | `uyen@edugpa.local` | Khách hàng |

Đăng nhập tại [dang-nhap.php](dang-nhap.php); quản trị viên được chuyển đến [quan-tri.php](quan-tri.php), khách về trang chủ. Database nâng cấp có thể giữ tài khoản/mật khẩu cũ khác seed.

## 4. Chức năng chính

| Trang | Chức năng |
| --- | --- |
| [index.php](index.php) | Trang chủ, giới thiệu hệ thống. |
| [danh-sach.php](danh-sach.php) | Tìm kiếm, lọc, sắp xếp và xem danh sách môn học. |
| [chi-tiet.php?id=1](chi-tiet.php?id=1) | Xem chi tiết một môn học theo `?id=`. |
| [gio-hang.php](gio-hang.php) | Quản lý kế hoạch học tập: thêm, cập nhật số lượng, xóa và xóa toàn bộ. |
| [lien-he.php](lien-he.php) | Gửi liên hệ, có kiểm tra dữ liệu và tải ảnh minh họa. |
| [dang-ky.php](dang-ky.php) | Đăng ký tài khoản khách hàng. |
| [dang-nhap.php](dang-nhap.php) | Đăng nhập bằng tên hoặc email. |
| [quan-tri.php](quan-tri.php) | Xem liên hệ đã nhận; chỉ tài khoản quản trị. |
| `thanhvien/*/gioithieu.php` | Trang giới thiệu và chức năng riêng của từng thành viên. |

## 5. Cấu trúc thư mục

```text
ltweb-doan-nhom03/
├── css/                 CSS dùng chung và CSS từng trang
├── data/                Dữ liệu tệp cũ của Bài 5
├── database/            Schema, seed và migration MySQL
├── api/                 API JSON môn học và giỏ học tập
├── kiemtra/             Ảnh và kết quả kiểm thử
├── images/              Ảnh dùng chung
├── inc/                 Cấu hình, header, footer, kiểm soát truy cập
├── js/                  JavaScript dùng chung
├── src/
│   ├── Data/            Lớp đọc/ghi dữ liệu
│   ├── Models/          Mô hình `MonHoc`
│   └── Services/        Nghiệp vụ kế hoạch học tập
├── storage/             Liên hệ và log ứng dụng (không commit dữ liệu runtime)
├── uploads/             Ảnh đính kèm liên hệ (không commit dữ liệu runtime)
├── thanhvien/           Trang cá nhân các thành viên
├── 404.php
├── 500.php
├── composer.json
└── README.md
```

## 6. Dữ liệu và trạng thái

- Môn học, danh mục và người dùng: MySQL `ltweb_nhom03` qua PDO. Dữ liệu JSON của Bài 5 còn trong kho để đối chiếu.
- Kế hoạch học tập: khách dùng session; tài khoản lưu vào `gio_hang_nguoi_dung`. Đăng nhập gộp giỏ khách, đăng xuất về giỏ khách trống, đăng nhập lại khôi phục giỏ đã lưu.
- Yêu thích: khách dùng localStorage; tài khoản dùng bảng `mon_yeu_thich`, tách riêng theo người dùng. Danh sách localStorage cũ được chuyển vào tài khoản khi đăng nhập.
- Liên hệ: `storage/lien-he.jsonl`; trang quản trị hiển thị bản ghi mới nhất trước.
- Ảnh liên hệ: `uploads/`.
- Log PHP: `logs/php-error.log`.
- Dữ liệu cá nhân đã tích hợp: các bảng `tv_binh_chon`, `tv_tra_cuu_mang`, `tv_luot_xem`; phần lưu JSONL cũ không được dùng để ghi thêm lịch sử tra cứu.

Các thư mục `storage/`, `uploads/` và `logs/` cần có quyền ghi cho tiến trình Apache/PHP. Dữ liệu runtime trong các thư mục này được `.gitignore` bỏ qua.

## 7. Kiểm thử thủ công

### Danh sách, chi tiết và kế hoạch học tập

1. Mở `danh-sach.php`, tìm kiếm/lọc/sắp xếp môn học.
2. Chọn **Thêm vào kế hoạch học tập**.
3. Hệ thống ở lại trang hiện tại, hiển thị toast thành công ở góc phải và số lượng giỏ hàng tăng.
4. Nút của môn đã thêm đổi thành **Đã thêm vào kế hoạch học tập** và bị vô hiệu hóa trên cả danh sách lẫn trang chi tiết.
5. Mở `gio-hang.php`, thử cập nhật số lượng, xóa một môn và xóa toàn bộ.
6. Đăng nhập tài khoản A, thêm giỏ/yêu thích rồi đăng xuất. Tài khoản B có dữ liệu riêng; đăng nhập lại A thấy dữ liệu đã lưu. Thử lại trong trình duyệt mới.

### Liên hệ và quản trị

1. Mở `lien-he.php`.
2. Nhập họ tên từ 3 ký tự, email hợp lệ, chọn chủ đề và nhập nội dung tối thiểu 10 ký tự.
3. Gửi thành công sẽ redirect theo PRG; bản ghi mới xuất hiện trong `storage/lien-he.jsonl`.
4. Đăng nhập bằng tài khoản mẫu, mở `quan-tri.php` và kiểm tra liên hệ ở đầu bảng.
5. Kiểm tra nhãn chủ đề ở quản trị khớp nhãn trên form, không hiển thị mã nội bộ như `gpa` hoặc `taikhoan`.

### Đăng nhập và dữ liệu không tin cậy

1. Đăng nhập sai: hiển thị thông báo chung, không lộ mật khẩu.
2. Gửi trường đăng nhập dạng mảng, ví dụ `email[]=admin`: phải báo lỗi đăng nhập, không phải HTTP 500.
3. Tài khoản khách mở `quan-tri.php`: nhận HTTP 403.
4. Truy cập trực tiếp `quan-tri.php` khi chưa đăng nhập: phải chuyển về `dang-nhap.php`.

### Lỗi dữ liệu và kết nối

Bài 5 đã có xử lý tệp JSON lỗi; phiên bản MySQL hiện dùng ngoại lệ PDO và handler trong `inc/config.php`. Khi kiểm thử kết nối lỗi, trang/API phải phản hồi theo cấu hình môi trường và ghi chi tiết vào `logs/php-error.log`. API luôn trả JSON 500 `{"loi":"Lỗi máy chủ"}`; khôi phục cấu hình ngay sau khi thử.

### Upload ảnh avatar của Hải

1. Mở `thanhvien/3120224042_hai/gioithieu.php`.
2. Chỉ ảnh JPEG, PNG hoặc WebP thật, tối đa 2MB, được chấp nhận.
3. Tên tệp do người dùng gửi không được sử dụng khi lưu: PNG thật đặt tên `.php` vẫn phải được lưu bằng phần mở rộng `.png` do server xác định.

### Responsive và tài nguyên

1. Dùng DevTools giả lập chiều rộng 360px tại `danh-sach.php`.
2. Trang không được cuộn ngang; lưới thẻ môn tự co theo chiều rộng phần nội dung.
3. Mở DevTools → Network tại trang Hải và Anh Trúc; CSS/JS chung và riêng phải trả HTTP 200, không có 404.

## 8. Bảo mật và kiểm tra dữ liệu

- Hàm `e()` trong `inc/ham.php` được dùng để escape HTML.
- Mật khẩu lưu dưới dạng băm, kiểm tra bằng `password_verify()`; truy vấn có dữ liệu người dùng dùng prepared statement.
- Đăng nhập tạo mã session mới để giảm rủi ro session fixation.
- Trang quản trị kiểm tra quyền bằng `yeuCauQuyen('quan_tri')` trong `inc/xac-thuc.php`; session chỉ giữ `uid` làm thông tin xác thực.
- Dữ liệu POST được kiểm tra kiểu và giá trị trước khi xử lý.
- Upload kiểm tra kích thước, MIME thực tế (`finfo`), nội dung ảnh (`getimagesize`) và tự xác định phần mở rộng an toàn ở server.
- Các luồng POST thành công sử dụng Post/Redirect/Get (PRG), tránh gửi lại biểu mẫu khi F5.

## 9. Xử lý lỗi

- `404.php`: trang hoặc môn học không tồn tại.
- `500.php`: lỗi máy chủ/dữ liệu không đọc được.
- `inc/config.php`: cấu hình log và exception handler chung.
- Trong môi trường phát triển, lỗi được ghi tại `logs/php-error.log`.

## 10. Thành viên

Các trang cá nhân nằm trong `thanhvien/`:

- `3120224034_Duy`
- `3120224042_hai`
- `3120224064_hop`
- `3120224159_anhtruc`
- `3120224174_uyen`

## 11. Lệnh kiểm tra nhanh

Tại thư mục dự án:

```bash
php -l danh-sach.php
php -l chi-tiet.php
php -l gio-hang.php
php -l lien-he.php
php -l dang-nhap.php
php -l quan-tri.php
```

Nếu dùng Git:

```bash
git diff --check
```

## 12. Upload và log runtime

Ảnh liên hệ và avatar Hải dùng chung kiểm tra tối đa 2 MB, MIME và nội dung ảnh. Tên ngẫu nhiên lưu trong `uploads/`, tự tạo thư mục khi cần. Thông tin avatar lưu trong `storage/3120224042_avatar.json`. Đăng nhập sai ghi tại `logs/php-error.log`, không dùng `data/login-error.log`.

| Chức năng | URL | Tệp PHP |
| --- | --- | --- |
| Trang chủ | `index.php` | `index.php` |
| Tìm kiếm | `danh-sach.php` | `danh-sach.php` |
| Chi tiết | `chi-tiet.php?id=1` | `chi-tiet.php` |
| Kế hoạch | `gio-hang.php` | `gio-hang.php` |
| Liên hệ | `lien-he.php` | `lien-he.php` |
| Đăng nhập | `dang-nhap.php` | `dang-nhap.php` |
| Quản trị | `quan-tri.php` | `quan-tri.php` |

## 13. Bổ sung Bài 6 — MySQL và REST API

| Endpoint | Phương thức | Chức năng |
| --- | --- | --- |
| `api/san-pham.php?q=Web` | GET | Tìm môn; hỗ trợ `dm`, `tc_toi_da`, `sx`, `trang` |
| `api/san-pham.php?id=1` | GET | Chi tiết một môn, chỉ trả trường công khai |
| `api/gio-hang.php` | GET | Giỏ của session và token `csrf` |
| `api/gio-hang.php` | POST | JSON `{"id":1,"sl":2}`, cùng cookie và header `X-CSRF-Token` |

Phản hồi có `Content-Type: application/json; charset=utf-8`. Các mã: 200 thành công; 400 JSON/ID sai; 401 token giỏ thiếu/sai; 404 môn không có; 405 sai phương thức kèm Allow; 422 dữ liệu/số lượng sai; 500 lỗi máy chủ.

Danh sách tìm kiếm có debounce 350ms; thêm giỏ bằng fetch cập nhật số món và nút ngay. Giữ form GET/POST dự phòng khi tắt JavaScript. Kế hoạch giới hạn số lượng 1–5, không thêm trùng môn; giỏ tài khoản được lưu MySQL để khôi phục khi đăng nhập lại. API `api/yeu-thich.php` hỗ trợ GET/POST, yêu cầu đăng nhập và token CSRF cho POST.

Kiểm thử API bằng [api.http](api.http), đổi `@goc` theo cách chạy. Ảnh và kết quả kiểm thử của các thành viên lưu tại `kiemtra/`; số liệu Bảng 2–5 và ERD được tổng hợp trong báo cáo chung.

Hiện việc chuyển MySQL chưa hoàn tất toàn bộ: liên hệ vẫn lưu JSONL, chức năng giao dịch đăng ký/đơn hàng và ERD còn cần ghép; seed mới có 8 môn. Chỉ ghi tính năng đã có và kết quả đã đo khi chốt bài.
