# EduGPA — Đồ án nhóm 03

Website PHP thuần hỗ trợ tra cứu môn học, lập kế hoạch học tập, tài khoản và các trang cá nhân. Dự án đang chuyển từ dữ liệu tệp của Bài 5 sang MySQL/PDO theo Bài tập nhóm 6.

## 1. Môi trường

- PHP >= 8.1; đã chạy thử với PHP 8.2 trên XAMPP.
- MySQL/MariaDB, bật các extension PHP `pdo_mysql`, `mbstring`, `fileinfo`.
- Composer để nạp các lớp theo PSR-4.
- Apache/XAMPP hoặc máy chủ tích hợp của PHP; cả hai đều cần MySQL chạy.
- PHP cần quyền ghi `storage/`, `logs/`, `uploads/`.

## 2. Cài đặt mới

1. Đặt mã nguồn tại `C:\xampp\htdocs\ltweb-doan-nhom03` hoặc clone repository vào thư mục làm việc.
2. Tại thư mục dự án, chạy:

   ```bash
   composer install
   ```

3. Bật MySQL trong XAMPP.
4. Import `database/schema.sql`, sau đó `database/seed.sql` bằng phpMyAdmin.

   **Lưu ý:** schema có `DROP DATABASE IF EXISTS ltweb_nhom03`. Chỉ dùng để dựng mới hoặc chủ động tạo lại dữ liệu; sao lưu trước nếu database đã có dữ liệu cần giữ.

   Với CMD/Git Bash có lệnh mysql:

   ```bash
   mysql -u root < database/schema.sql
   mysql -u root ltweb_nhom03 < database/seed.sql
   ```

   Không dùng cú pháp chuyển hướng `<` trực tiếp trong PowerShell; dùng phpMyAdmin hoặc chạy các lệnh trên trong CMD.

5. Chọn một cách chạy website:

   - XAMPP: bật Apache, mở http://localhost/ltweb-doan-nhom03/
   - PHP tích hợp:

     ```bash
     php -S localhost:8000
     ```

     Mở http://localhost:8000/

Không mở tệp PHP trực tiếp bằng đường dẫn ổ đĩa.

### Kết nối PDO

`inc/db.php` cung cấp `db()`, mặc định:

| Thuộc tính | Giá trị |
| --- | --- |
| Host | `127.0.0.1` |
| Port | `3306` |
| Database | `ltweb_nhom03` |
| User | `root` |
| Password | Rỗng |
| Charset | `utf8mb4` |

Có thể đặt `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` trong môi trường PHP/Apache. Database cố định là `ltweb_nhom03`. Kết nối dùng `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`.

### Nâng cấp database cũ, giữ dữ liệu

Nếu đã có database phiên bản trước khi thêm email, sao lưu rồi chạy:

```bash
php database/migrate-email.php
```

Migration bổ sung email cho tài khoản hiện có theo dạng `<ten_dang_nhap>@edugpa.local`, tạo bảng lượt xem và dòng thành viên Hải nếu thiếu; không đổi mật khẩu hoặc xóa phiếu bầu/lịch sử. Đây không phải migration tổng quát cho mọi phiên bản và không thay thế schema khi cài mới.

## 3. Đăng nhập và tài khoản thử

Mở `dang-nhap.php`; có thể nhập **tên đăng nhập hoặc email** trong cùng một ô.

Các tài khoản sau được tạo bởi seed hiện tại, mật khẩu đều là `123456`:

| Tên đăng nhập | Email | Vai trò |
| --- | --- | --- |
| `admin` | `admin@edugpa.local` | `quan_tri` |
| `truc` | `truc@edugpa.local` | `khach_hang` |
| `duy` | `duy@edugpa.local` | `khach_hang` |
| `uyen` | `uyen@edugpa.local` | `khach_hang` |

Nếu giữ lại database local cũ, tài khoản có thể khác seed. Ví dụ `an`, `binh` vẫn có thể dùng mật khẩu cũ `matkhau123`; không giả định chúng tồn tại trên bản cài mới.

Đăng nhập dùng `password_verify()`, đổi mã phiên và lưu `uid`; tên/quyền đọc từ database. Quản trị viên vào trang quản trị, khách về trang chủ. Đăng xuất xóa đăng nhập và đổi mã phiên nhưng **giữ giỏ học tập**, độc lập với tài khoản.

## 4. Chức năng và URL

Các URL dưới đây tương đối với thư mục website.

| Chức năng | URL | Tệp PHP |
| --- | --- | --- |
| Trang chủ | `index.php` | `index.php` |
| Tìm kiếm, lọc, sắp xếp, phân trang | `danh-sach.php?q=Web` | `danh-sach.php` |
| Chi tiết môn học | `chi-tiet.php?id=1` | `chi-tiet.php` |
| Kế hoạch học tập | `gio-hang.php` | `gio-hang.php` |
| Liên hệ và ảnh đính kèm | `lien-he.php` | `lien-he.php` |
| Đăng ký | `dang-ky.php` | `dang-ky.php` |
| Đăng nhập | `dang-nhap.php` | `dang-nhap.php` |
| Đăng xuất, giữ giỏ | `dang-xuat.php` | `dang-xuat.php` |
| Xem liên hệ, chỉ quản trị | `quan-tri.php` | `quan-tri.php` |
| Bình chọn và thống kê Hợp | `thanhvien/3120224064_hop/gioithieu.php#binh-chon` | Trang Hợp, `inc/binh-chon-hop*.php` |
| Tra cứu IP/domain và lịch sử Hợp | `thanhvien/3120224064_hop/gioithieu.php#cong-cu-section` | Trang Hợp, `KhoTraCuuMang` |
| Trang cá nhân thành viên | `thanhvien/<MSSV>_<ten>/gioithieu.php` | Các thư mục trong `thanhvien/` |

## 5. REST API và JavaScript — chức năng 6

| Endpoint | Phương thức | Mục đích |
| --- | --- | --- |
| `api/san-pham.php?q=Web&dm=&tc_toi_da=&sx=moi&trang=1` | GET | Tìm môn, lọc, sắp xếp, phân trang 6 môn/trang |
| `api/san-pham.php?id=1` | GET | Xem một môn |
| `api/gio-hang.php` | GET | Đọc giỏ của session và token `csrf` |
| `api/gio-hang.php` | POST | Thêm môn với JSON `{"id":1,"sl":2}` |

Mọi phản hồi có `Content-Type: application/json; charset=utf-8`. Mô hình `MonHoc` implement `JsonSerializable`, chỉ trả `id`, `maMon`, `tenMon`, `soTinChi`, `anh`, `trangThai`; không trả điểm, kết quả hoặc các cột nội bộ khác.

POST giỏ cần cùng cookie session và header `X-CSRF-Token`, lấy từ GET giỏ hoặc meta `csrf-gio-hang` của trang. Không cần đăng nhập để dùng giỏ riêng của phiên; thiếu/sai token trả 401.

| Mã | Trường hợp |
| --- | --- |
| 200 | Thành công |
| 400 | JSON sai cú pháp hoặc ID chi tiết sai định dạng |
| 401 | Token phiên giỏ hàng thiếu/không hợp lệ |
| 404 | Môn học không tồn tại |
| 405 | Sai phương thức, có header Allow |
| 422 | Tham số sai kiểu hoặc số lượng ngoài 1–5 |
| 500 | Lỗi máy chủ; body chỉ `{"loi":"Lỗi máy chủ"}` |

`js/tim-kiem.js` debounce 350ms, hủy request cũ, tránh phản hồi cũ ghi đè và giữ bộ lọc/phân trang. `js/gio-hang.js` cập nhật `#cart-count` bằng `textContent`, đổi nút đã thêm và hiện toast. Giỏ đọc bằng một truy vấn `IN (...)`, không truy vấn trong vòng lặp. Thêm lại môn có sẵn không tăng số lượng; `so_mon` là tổng số lượng giống header PHP.

Các form vẫn có action GET/POST để hoạt động khi tắt JavaScript. Chạy bộ request trong `api.http` bằng VS Code REST Client; sửa `@goc` theo URL đang chạy.

## 6. Phần cá nhân Hợp

- MSSV `3120224064`; dữ liệu thành viên nằm trong bảng `thanh_vien`.
- `tv_binh_chon`: mỗi tài khoản một phiếu trên trang Hợp nhờ UNIQUE `(mssv, nguoi_dung_id)`; bắt lỗi 1062, thống kê bằng GROUP BY và hiển thị số phiếu/tỉ lệ.
- `tv_tra_cuu_mang`: thay log JSONL cũ bằng MySQL; tra cứu IP/domain và hiển thị 10 kết quả gần nhất. Không hiển thị độ trễ giả và không lưu mật khẩu từ công cụ entropy.
- POST thành công chuyển hướng PRG; thông báo session đọc một lần, F5 không gửi lại dữ liệu.
- Thông báo bình chọn thành công/đã bình chọn hiện popup xanh bên phải; lỗi xử lý hiện popup đỏ.
- Để thử phiếu mới, dùng tài khoản chưa bình chọn; seed đã tạo một phiếu mẫu của khách đầu tiên.
- Lịch sử JSONL từ bản cũ không tự nhập hoặc xóa.

## 7. Dữ liệu và cấu trúc

```text
api/              API môn học và giỏ
database/         schema.sql, seed.sql, migrate-email.php
inc/              config.php, db.php, xac-thuc.php, helper, giao diện chung
src/Data/         Lớp truy cập dữ liệu
src/Models/       Model môn học, giới hạn dữ liệu JSON
src/Services/     Kế hoạch học tập và upload
js/               main.js, tim-kiem.js, gio-hang.js
thanhvien/        Trang cá nhân các thành viên
kiemtra/          Ảnh và ghi nhận kiểm thử
storage/          Liên hệ JSONL, dữ liệu runtime
uploads/          Ảnh runtime
logs/             php-error.log
api.http          Request kiểm thử
README.md         Hướng dẫn
```

- Môn học, danh mục, người dùng, bình chọn, tra cứu mạng và lượt xem dùng MySQL.
- Giỏ hiện lưu session `ke_hoach_dang_ky`; các môn trong giỏ đọc từ MySQL.
- Liên hệ vẫn lưu `storage/lien-he.jsonl` qua `KhoLienHe`, dù schema đã có bảng `lien_he`.
- Dữ liệu `data/*.json` còn trong repository là phần cũ; `KhoMonHoc` hiện truy vấn MySQL.
- `.gitignore` loại dữ liệu runtime, vendor và ảnh upload; `uploads/.gitkeep` giữ thư mục trên bản clone mới.

## 8. Kiểm thử và minh chứng

1. Đăng nhập bằng cả `admin` và `admin@edugpa.local`; nhập sai/đầu vào mảng phải báo lỗi, không Warning. Khách vào quản trị nhận 403.
2. Tìm nhanh `web` ở danh sách: kết quả đổi, Network có API; xóa Network rồi gõ liền mạch để kiểm tra debounce.
3. Thêm môn từ danh sách/chi tiết: không reload, số món tăng, nút đổi; thêm trùng không tăng. Đăng xuất vẫn giữ giỏ.
4. Tắt JavaScript: dùng nút Lọc kết quả và form thêm giỏ; tải lại trang nhưng thao tác vẫn thành công.
5. Trang Hợp: vote một lần, vote trùng bị chặn; F5 xóa thông báo. Tra cứu `8.8.8.8`, xem lịch sử và kiểm tra F5 không thêm bản ghi.
6. Chạy các ca lỗi trong `api.http`; không suy ra tất cả mã lỗi chỉ từ ảnh 404.
7. W3C: Ctrl+U lấy HTML do PHP sinh ra rồi kiểm tra Text input; không đưa nguyên PHP vào validator. Đo Lighthouse/360px theo bảng yêu cầu của đề.

Ảnh Bài 6 của Hợp tại `kiemtra/hop-bai6-*`: W3C không lỗi, vote thành công/trùng, lịch sử MySQL, tìm kiếm một request, fetch giỏ, API 404 và thêm giỏ khi tắt JavaScript. Kết quả kiểm thử chức năng 6 trước đó ghi trong `kiemtra/hop-chuc-nang-6.md`; các ghi chú “chưa đo” trong tệp là trạng thái tại thời điểm ghi nhận, không thay thế bằng chứng bổ sung sau đó. Bảng 3–5 trong báo cáo cần điền theo các lượt kiểm tra thực tế, không mặc định đạt toàn bộ website.

Kiểm tra cú pháp và khoảng trắng tại thư mục dự án:

```powershell
Get-ChildItem -Recurse -Filter *.php -File |
  Where-Object FullName -NotMatch '\\vendor\\' |
  ForEach-Object { php -l $_.FullName }
git diff --check
```

## 9. Trạng thái ghép bài và giới hạn hiện tại

- Schema hiện có 11 bảng; seed có 8 môn/4 danh mục đều có dữ liệu, 4 tài khoản và 2 dòng thành viên. Chưa đạt yêu cầu tối thiểu 30 môn và danh mục trống; các thành viên cần bổ sung trước khi chốt bài.
- Chưa có `database/erd.png`, bảng dự án/kỹ năng riêng của các thành viên còn lại trong schema hiện tại.
- Chưa có trang đặt hàng/đơn hàng hoặc giao dịch đăng ký tương đương; hai bảng kế hoạch trong schema chưa được dùng để tạo giao dịch.
- Không có cột tồn kho của môn học. API giỏ hiện kiểm tra giới hạn số lượng 1–5; điều này không thay thế kiểm thử tồn kho/transaction của chức năng 5.
- Trang quản trị hiện xem liên hệ, chưa xác nhận hoàn tất CRUD chung.
- README mô tả code đang có, không xác nhận cả nhóm đã hoàn thành toàn bộ đề.

Khi đóng gói, lấy phiên bản Git cuối đã thống nhất, loại runtime/log/backup và thử chạy bản giải nén theo hướng dẫn cài mới. Không chạy schema có DROP DATABASE lên dữ liệu đang dùng. Tên PDF/ZIP bắt đầu bằng `Nhom03`.

## 10. Thành viên và phân công chức năng chung

| Thành viên | MSSV | Phân công mới của nhóm |
| --- | --- | --- |
| Lê Nguyễn Anh Trúc | 3120224159 | Chức năng 1 |
| Huỳnh Phương Uyên | 3120224174 | Chức năng 2 và 4 |
| Lê Dương Hoàng Hải | 3120224042 | Chức năng 3 |
| Dương Bảo Duy | 3120224034 | Chức năng 5 |
| Phan Văn Hợp | 3120224064 | Chức năng 6; bình chọn/lịch sử tra cứu ở trang cá nhân |

Mỗi thành viên cập nhật schema/seed, minh chứng và commit phần của mình; nhóm trưởng ghép ERD, bảng kiểm thử và báo cáo cuối.
