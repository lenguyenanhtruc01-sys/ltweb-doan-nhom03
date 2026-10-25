# EduGPA — Đồ án nhóm 03

## Chức năng 6 — REST API và fetch của Hợp

| Endpoint | Phương thức | Chức năng |
| --- | --- | --- |
| `api/san-pham.php?q=Web&dm=&tc_toi_da=&sx=moi&trang=1` | GET | Tìm môn, lọc và phân trang 6 môn/trang |
| `api/san-pham.php?id=1` | GET | Một môn học, JSON qua `MonHoc::jsonSerialize()` |
| `api/gio-hang.php` | GET | Giỏ của session hiện tại và token `csrf` |
| `api/gio-hang.php` | POST | JSON `{"id":1,"sl":2}`, header `X-CSRF-Token` lấy từ GET giỏ hoặc meta trên trang |

API công khai môn học; giỏ thuộc cookie phiên và không phụ thuộc tài khoản quản trị. POST cần token của cùng phiên; sai/thiếu token nhận 401. Mã khác: 400 JSON/ID sai, 404 môn không tồn tại, 405 kèm Allow, 422 tham số/số lượng sai, 500 chỉ `{"loi":"Lỗi máy chủ"}`. Mọi phản hồi có `Content-Type: application/json; charset=utf-8`. `so_mon` là tổng số lượng giống header PHP; thêm lại môn có sẵn không tăng số lượng (giữ quy tắc kế hoạch của nhóm).

Đề tài dùng môn học: không có cột tồn kho, số lượng giữ giới hạn 1–5 hiện có; `sl=6` nhận 422. Kiểm thử tồn kho/giao dịch chỉ áp dụng sau khi nhóm bổ sung nghiệp vụ đăng ký có hạn mức, không khẳng định đã làm chức năng đặt hàng.

`js/tim-kiem.js` debounce 350ms, hủy request cũ và kiểm tra thứ tự phản hồi; giữ bộ lọc, sắp xếp, phân trang. `js/gio-hang.js` nâng cấp form ở danh sách/chi tiết, đổi nút đã thêm, toast xanh bên phải và cập nhật `#cart-count` bằng textContent. Tắt JavaScript vẫn dùng form GET/POST với action cũ. Giỏ được đọc bằng một truy vấn IN thay cho truy vấn trong vòng lặp. Không trả cột nội bộ như ngày tạo, mô tả, điểm hoặc kết quả học tập qua JSON.

Kiểm thử API bằng `api.http` (VS Code REST Client). Chi tiết lượt kiểm tra thực tế: `kiemtra/hop-chuc-nang-6.md`. Các thay đổi file của thành viên khác chỉ là điểm tích hợp đã thống nhất: danh sách/chi tiết/header, model và lớp đọc giỏ.

## Bài 6 — phần cá nhân Phan Văn Hợp

Trang: `thanhvien/3120224064_hop/gioithieu.php`.

- Tra cứu mạng ghi bảng `tv_tra_cuu_mang`, hiển thị 10 lần gần nhất; không ghi thêm JSONL.
- Bình chọn `tv_binh_chon`: mỗi tài khoản một phiếu, số phiếu và tỉ lệ theo lựa chọn; PRG và thông báo một lần.
- PDO chung: `inc/db.php`, database `ltweb_nhom03`, XAMPP mặc định root/mật khẩu rỗng; bật `pdo_mysql`.
- SQL của Hợp đã ghép vào `database/schema.sql`, `database/seed.sql` cùng các bảng và dữ liệu chung từ GitHub. Lưu ý: schema chung có `DROP DATABASE`, chỉ import khi chủ động dựng lại CSDL và đã sao lưu dữ liệu cần giữ. Việc đồng bộ Git không tự import SQL.
- Đăng nhập đã nối bảng `nguoi_dung`, kiểm tra `password_verify()`, đổi mã session và chỉ lưu `uid` làm định danh. Header đọc tên/quyền từ CSDL, khách không vào quản trị được. Đăng xuất vẫn giữ kế hoạch học tập độc lập.
- Log JSONL cũ được giữ trên máy để đối chiếu, không tự nhập hay xóa lịch sử cũ.

Kiểm thử sau khi ghép CSDL: tra cứu `127.0.0.1`, xem lịch sử rồi F5 (không thêm dòng); vote một lần rồi vote lại (báo đã bình chọn); F5 thông báo biến mất; thống kê có tỉ lệ và tổng đúng; gửi `target[]`/`tuy_chon[]` không phát Warning. Chụp bằng chứng vào `kiemtra/`, điền Bảng 5 và tự commit/push từ tài khoản cá nhân.

Các phần đặt hàng/REST API của Hợp chưa có trong checkout hiện tại; mục này chỉ mô tả phần cá nhân đã tích hợp, không xác nhận hoàn thành toàn bộ Bài 6.

### Chạy thử phần cá nhân sau khi ghép MySQL

- Bật Apache và MySQL trong XAMPP; mở `dang-nhap.php`.
- Đăng nhập dùng email theo bản GitHub mới. Dữ liệu local đã được giữ lại: `admin@edugpa.local` / `123456`, `an@edugpa.local` và `binh@edugpa.local` / `matkhau123`. Khách đăng nhập về trang chủ; quản trị tới trang quản trị. Nếu dựng mới từ seed, dùng các email và mật khẩu ghi trong `database/seed.sql`.
- Máy có CSDL từ phiên bản cũ: sao lưu trước, chạy `php database/migrate-email.php` để bổ sung email và lượt xem mà không xóa dữ liệu. Không import lại schema có DROP DATABASE.
- Mở `thanhvien/3120224064_hop/gioithieu.php#binh-chon`. Dữ liệu mẫu đã có một phiếu của `an`; lượt kiểm thử local tạo thêm phiếu `sang_tao` của `binh`, nên hai tài khoản này sẽ báo đã bình chọn nếu gửi tiếp.
- Ngày 24/10/2026 đã tạo CSDL local khi xác nhận chưa tồn tại, nạp schema/seed mà không chạy DROP. Kiểm thử HTTP đã đạt: vote thành công, chống trùng, flash một lần, lưu lịch sử, F5 không ghi thêm, khách nhận 403 ở quản trị, đăng xuất và đầu vào dạng mảng. Kết quả local không có nghĩa hosting khác đã được import CSDL.
- Mở bằng `php -S localhost:8000` cũng được; máy chủ thử nghiệm tạm cổng 8097 đã được dừng sau kiểm tra.

Website PHP hỗ trợ sinh viên tra cứu môn học, lập kế hoạch học tập, gửi liên hệ và quản trị các liên hệ đã nhận.

## 1. Yêu cầu môi trường

- PHP 8.1 trở lên (đã thử nghiệm với PHP 8.2).
- Apache có PHP, ví dụ XAMPP.
- Tiện ích PHP `fileinfo` và `mbstring`.
- Composer (chỉ cần khi thư mục `vendor/` chưa có).

## 2. Cài đặt và chạy

1. Chép thư mục dự án vào `C:\xampp\htdocs\ltweb-doan-nhom03`.
2. Mở XAMPP Control Panel và khởi động Apache.
3. Nếu thiếu thư mục `vendor/`, chạy tại thư mục dự án:

   ```bash
   composer install
   ```

4. Mở trang chủ:

   ```text
   http://localhost/ltweb-doan-nhom03/
   ```

Không chạy dự án trực tiếp bằng cách mở tệp PHP trên ổ đĩa, vì session, upload và chuyển hướng HTTP sẽ không hoạt động đúng.

### Chạy không cần Apache

```bash
composer install
php -S localhost:8000
```

Mở http://localhost:8000/index.php. Thư mục uploads được tự tạo khi cần; PHP cần quyền ghi tại thư mục dự án.

## 3. Tài khoản quản trị mẫu

| Tên đăng nhập | Mật khẩu |
| --- | --- |
| `admin` | `123456` |

Đăng nhập tại [dang-nhap.php](dang-nhap.php), sau đó hệ thống chuyển đến [quan-tri.php](quan-tri.php).

## 4. Chức năng chính

| Trang | Chức năng |
| --- | --- |
| [index.php](index.php) | Trang chủ, giới thiệu hệ thống. |
| [danh-sach.php](danh-sach.php) | Tìm kiếm, lọc, sắp xếp và xem danh sách môn học. |
| [chi-tiet.php?id=1](chi-tiet.php?id=1) | Xem chi tiết một môn học theo `?id=`. |
| [gio-hang.php](gio-hang.php) | Quản lý kế hoạch học tập: thêm, cập nhật số lượng, xóa và xóa toàn bộ. |
| [lien-he.php](lien-he.php) | Gửi liên hệ, có kiểm tra dữ liệu và tải ảnh minh họa. |
| [dang-nhap.php](dang-nhap.php) | Đăng nhập quản trị. |
| [quan-tri.php](quan-tri.php) | Xem liên hệ đã nhận; yêu cầu đăng nhập. |
| `thanhvien/*/gioithieu.php` | Trang giới thiệu và chức năng riêng của từng thành viên. |

## 5. Cấu trúc thư mục

```text
ltweb-doan-nhom03/
├── css/                 CSS dùng chung và CSS từng trang
├── data/                Dữ liệu môn học (`mon-hoc.json`)
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

- Danh sách môn học: `data/mon-hoc.json`.
- Kế hoạch học tập: session PHP, độc lập với tài khoản quản trị và vẫn được giữ khi đăng xuất.
- Liên hệ: `storage/lien-he.jsonl`; trang quản trị hiển thị bản ghi mới nhất trước.
- Ảnh liên hệ: `uploads/`.
- Log PHP: `logs/php-error.log`.
- Log tra cứu mạng của trang Hợp: `storage/3120224064_netlog.jsonl`.

Các thư mục `storage/`, `uploads/` và `logs/` cần có quyền ghi cho tiến trình Apache/PHP. Dữ liệu runtime trong các thư mục này được `.gitignore` bỏ qua.

## 7. Kiểm thử thủ công

### Danh sách, chi tiết và kế hoạch học tập

1. Mở `danh-sach.php`, tìm kiếm/lọc/sắp xếp môn học.
2. Chọn **Thêm vào kế hoạch học tập**.
3. Hệ thống ở lại trang hiện tại, hiển thị toast thành công ở góc phải và số lượng giỏ hàng tăng.
4. Nút của môn đã thêm đổi thành **Đã thêm vào kế hoạch học tập** và bị vô hiệu hóa trên cả danh sách lẫn trang chi tiết.
5. Mở `gio-hang.php`, thử cập nhật số lượng, xóa một môn và xóa toàn bộ.
6. Đăng nhập rồi đăng xuất; kế hoạch học tập vẫn không phụ thuộc tài khoản quản trị.

### Liên hệ và quản trị

1. Mở `lien-he.php`.
2. Nhập họ tên từ 3 ký tự, email hợp lệ, chọn chủ đề và nhập nội dung tối thiểu 10 ký tự.
3. Gửi thành công sẽ redirect theo PRG; bản ghi mới xuất hiện trong `storage/lien-he.jsonl`.
4. Đăng nhập bằng tài khoản mẫu, mở `quan-tri.php` và kiểm tra liên hệ ở đầu bảng.
5. Kiểm tra nhãn chủ đề ở quản trị khớp nhãn trên form, không hiển thị mã nội bộ như `gpa` hoặc `taikhoan`.

### Đăng nhập và dữ liệu không tin cậy

1. Đăng nhập sai: hiển thị thông báo chung, không lộ mật khẩu.
2. Gửi `username[]=admin`: phải nhận phản hồi HTTP 200 với lỗi đăng nhập, không phải HTTP 500.
3. Truy cập trực tiếp `quan-tri.php` khi chưa đăng nhập: phải chuyển về `dang-nhap.php`.

### Tệp JSON lỗi

`KhoMonHoc` dùng `JSON_THROW_ON_ERROR`. Khi tệp môn học bị thiếu, không đọc được hoặc JSON sai, lớp dữ liệu ném ngoại lệ; handler chung sẽ ghi log và hiển thị `500.php`.

Khi kiểm thử trường hợp này, hãy sao lưu `data/mon-hoc.json` trước và khôi phục ngay sau khi hoàn tất.

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
- Mật khẩu tài khoản mẫu được kiểm tra bằng `password_verify()`.
- Đăng nhập tạo mã session mới để giảm rủi ro session fixation.
- Trang quản trị được bảo vệ bởi `inc/bao-ve.php`.
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
