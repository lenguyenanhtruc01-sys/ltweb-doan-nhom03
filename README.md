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
4. Tạo/nâng cấp CSDL bằng một lệnh tại thư mục dự án:

   ```bash
   php database/cai-dat.php
   ```

   PowerShell chưa nhận lệnh `php` thì dùng PHP của XAMPP:

   ```powershell
   C:\xampp\php\php.exe database\cai-dat.php
   ```

   Bộ cài tạo đủ bảng và bổ sung dữ liệu còn thiếu ở cả máy mới, máy có bản cũ và máy đã tạo bảng nhưng chưa nạp seed. Trước khi nâng cấp máy có dữ liệu, nó sao lưu các bảng vào tệp SQL ngoài webroot (đường dẫn được in ra). Mật khẩu, quyền, giỏ, yêu thích, chủ trang và kế hoạch hiện có được giữ nguyên.

   Dữ liệu mẫu được đối chiếu bằng tên đăng nhập, slug danh mục, mã môn và MSSV; không ép ID mẫu lên dữ liệu thật. Bộ cài dùng một CSDL mẫu tạm để kiểm tra schema/seed và gộp dữ liệu, sau đó xóa CSDL tạm đó; **không xóa CSDL dự án**. Tài khoản MySQL chạy bộ cài cần quyền tạo/xóa CSDL (root mặc định của XAMPP đáp ứng).

5. Mở http://localhost/ltweb-doan-nhom03/

Kết nối chung trong `inc/db.php`: database `ltweb_nhom03`, host `127.0.0.1`, port `3306`, user `root`, mật khẩu rỗng, charset `utf8mb4`. Có thể cấu hình `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` qua môi trường PHP/Apache.

**Sau mỗi lần `git pull`:** chạy lại `php database/cai-dat.php`. Git chỉ kéo code; lệnh này áp dụng các nâng cấp CSDL đã được khai báo trong bộ cài. Chạy lại không đặt mật khẩu về mặc định. Chủ trang Hải chưa có sẽ được tạo bằng `hai / matkhau123`; nếu đã có chủ thì giữ nguyên tài khoản và mật khẩu. Ảnh upload/dữ liệu runtime của máy khác không được tải xuống qua Git.

Cài mới thủ công theo đề vẫn có thể import `database/schema.sql` rồi `database/seed.sql` bằng phpMyAdmin thay cho bộ cài. **Schema thủ công có `DROP DATABASE`**, không import lại để cập nhật máy đang có dữ liệu. Các lệnh nâng cấp riêng bên dưới chỉ cần khi muốn chạy từng bước thay cho bộ cài chung.

Với database cũ chưa có email, sao lưu rồi chạy `php database/migrate-email.php`; migration giữ tài khoản và dữ liệu, không thay thế toàn bộ schema.

Luồng của `database/cai-dat.php`: cài mới dùng schema/seed; nâng cấp sao lưu một lần trước thay đổi, tạo bảng/cột thiếu rồi gộp dữ liệu theo khóa tự nhiên. Bộ cài nạp bảy tệp `nang-cap-*.php`: kế hoạch, khóa ngoại môn học, số chỗ, cá nhân Hải, cá nhân Uyên, liên hệ và thời tiết Duy. Nhánh nâng cấp còn đọc `bo-sung-30-mon.sql`. Các bước email/kế hoạch/số chỗ/khóa ngoại chỉ chạy một lần mỗi lượt cài đặt; không chạy lại ở cuối nhánh nâng cấp.

Các tệp `migrate-email.php`, `luu-gio-va-yeu-thich.sql`, `nang-cap-chuc-nang-4.sql` là công cụ nâng cấp thủ công riêng, không được bộ cài gọi trực tiếp. Giữ chúng để hỗ trợ các cách nâng cấp cũ; không cần chạy thêm nếu đã dùng bộ cài chung. Không xóa/gộp các tệp migration chỉ vì tên hoặc nội dung gần giống nhau: chúng còn phục vụ nâng cấp từng phần và kiểm thử dữ liệu cũ.

Không mở trực tiếp tệp PHP bằng đường dẫn ổ đĩa.

Với CSDL đang dùng, `php database/cai-dat.php` sao lưu rồi tự tạo các bảng giỏ/yêu thích còn thiếu. Nếu cần chạy riêng thay cho bộ cài, có thể import `database/luu-gio-va-yeu-thich.sql`; không import lại schema có `DROP DATABASE`.

Nếu CSDL cũ thiếu cột trạng thái/ghi chú và thông tin môn trong chi tiết kế hoạch: sao lưu rồi chạy `php database/nang-cap-ke-hoach.php`. Script chỉ thêm cột thiếu, điền thông tin môn hiện tại cho chi tiết cũ và giữ nguyên snapshot đã có; chạy lại không tạo trùng dữ liệu. Bản cài mới từ schema không cần bước này.

Để chặn xóa môn đã có trong kế hoạch theo ca 5 của Bảng 3: sao lưu rồi chạy `php database/nang-cap-khoa-ngoai-mon-hoc.php`. Migration đổi FK môn học sang RESTRICT, giữ nguyên dữ liệu và chạy lại được. Schema cài mới đã dùng RESTRICT; xóa kế hoạch vẫn tự xóa các chi tiết của chính kế hoạch đó.

Avatar Hải dùng MySQL: sao lưu rồi chạy `php database/nang-cap-ca-nhan-hai.php`. Script giữ ảnh cũ, nhập tên ảnh từ JSON vào `tv_anh_dai_dien` và liên kết tài khoản chủ trang qua `thanh_vien.nguoi_dung_id`. Nếu chưa có chủ, dùng/tạo tài khoản mẫu `hai` bên dưới; có thể chỉ định ID tài khoản đã có bằng tham số CLI. Khi chạy mặc định hoặc qua bộ cài, chủ đã có phải khớp tài khoản `hai / hai@example.com`; nếu không khớp, script dừng và báo ID hai bên để kiểm tra, không tự đổi chủ. Tham số ID tường minh chỉ xác nhận chủ hiện có hoặc gán khi chưa có chủ, không ghi đè chủ khác. Không đổi mật khẩu hoặc ghi đè avatar mới khi chạy lại. Tệp JSON cũ chỉ còn dùng cho migration, trang cá nhân không đọc/ghi tệp này.

### Chạy không cần Apache

```bash
composer install
php -S localhost:8000
```

Mở http://localhost:8000/index.php; MySQL vẫn cần chạy. PHP cần quyền ghi vào `storage/`, `uploads/`, `logs/`.

## 3. Tài khoản thử

Đăng nhập bằng **tên đăng nhập hoặc email**. Sau khi cài mới bằng `database/schema.sql` và `database/seed.sql`, cả 5 tài khoản mẫu đều dùng mật khẩu `matkhau123`:

| Tên đăng nhập | Email | Vai trò |
| --- | --- | --- |
| `admin` | `admin@example.com` | Quản trị |
| `hop` | `hop@example.com` | Khách hàng |
| `uyen` | `uyen@example.com` | Khách hàng |
| `duy` | `duy@example.com` | Khách hàng, chủ trang cá nhân Duy |
| `hai` | `hai@example.com` | Khách hàng, chủ trang cá nhân Hải |

Đăng nhập tại [dang-nhap.php](dang-nhap.php); quản trị viên được chuyển đến [quan-tri.php](quan-tri.php), khách về trang chủ. Với CSDL đã có dữ liệu, chạy `php database/cai-dat.php` để bổ sung tài khoản mẫu còn thiếu; bộ cài không đổi tên/xóa `an`, `binh`, không đặt lại mật khẩu tài khoản đã có và giữ lịch sử kế hoạch của họ. Chủ Duy được gắn theo tài khoản `duy / duy@example.com` khi liên kết chủ còn trống, không ghi đè chủ hiện có. Nếu username/email hoặc chủ trên máy cũ khác seed, cần kiểm tra ánh xạ thực tế; không mặc định mật khẩu cũ là `matkhau123`.

## 4. Chức năng chính

| Trang | Chức năng |
| --- | --- |
| [index.php](index.php) | Trang chủ, giới thiệu hệ thống. |
| [danh-sach.php](danh-sach.php) | Tìm kiếm, lọc, sắp xếp và xem danh sách môn học. |
| [chi-tiet.php?id=1](chi-tiet.php?id=1) | Xem chi tiết một môn học theo `?id=`. |
| [gio-hang.php](gio-hang.php) | Quản lý kế hoạch học tập: thêm, cập nhật số lượng, xóa và xóa toàn bộ. |
| [dat-hang.php](dat-hang.php) | Nhận POST gửi kế hoạch của tài khoản đã đăng nhập, lưu bằng transaction. |
| [don-hang.php](don-hang.php) | Xem kế hoạch của mình; quản trị xem tất cả và đổi trạng thái. |
| [lien-he.php](lien-he.php) | Gửi liên hệ, có kiểm tra dữ liệu và tải ảnh minh họa. |
| [dang-ky.php](dang-ky.php) | Đăng ký tài khoản khách hàng. |
| [dang-nhap.php](dang-nhap.php) | Đăng nhập bằng tên hoặc email. |
| [quan-tri.php](quan-tri.php) | Xem liên hệ đã nhận; chỉ tài khoản quản trị. |
| `thanhvien/*/gioithieu.php` | Trang giới thiệu và chức năng riêng của từng thành viên. |

## 5. Cấu trúc thư mục

```text
ltweb-doan-nhom03/
├── css/                 CSS dùng chung và CSS từng trang
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

- Môn học, danh mục và người dùng: MySQL `ltweb_nhom03` qua PDO. JSON môn học và hai tệp xác thực cũ đã bỏ khỏi bản hiện tại; bản Bài 5 được giữ ở tag `btn5`.
- Kế hoạch học tập: khách dùng session; tài khoản lưu vào `gio_hang_nguoi_dung`. Đăng nhập gộp giỏ khách, đăng xuất về giỏ khách trống, đăng nhập lại khôi phục giỏ đã lưu.
- Giỏ tài khoản cập nhật từng môn trong MySQL và đọc lại khi tải trang/API; phiên cũ đăng xuất không ghi đè giỏ của phiên khác. Thao tác xóa hết vẫn chủ động xóa toàn bộ giỏ của tài khoản.
- Yêu thích: khách dùng localStorage; tài khoản dùng bảng `mon_yeu_thich`, tách riêng theo người dùng. Danh sách localStorage cũ được chuyển vào tài khoản khi đăng nhập.
- Liên hệ: bảng MySQL `lien_he` qua `KhoLienHe(PDO)`; trang quản trị hiển thị bản ghi mới nhất trước. JSONL cũ chỉ giữ làm bản lưu, chưa tự nhập vào MySQL.
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
3. Gửi thành công sẽ redirect theo PRG; bản ghi mới xuất hiện trong bảng `lien_he`.
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
- `403.php`: trang từ chối truy cập khi tài khoản thiếu quyền; trả HTTP 403 và không truy vấn CSDL.
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

Ảnh liên hệ, môn học và avatar Hải dùng chung kiểm tra tối đa 2 MB, MIME và nội dung ảnh. Tên ngẫu nhiên lưu trong `uploads/`, tự tạo thư mục khi cần. Thông tin avatar lưu trong MySQL `tv_anh_dai_dien`; chỉ tài khoản chủ trang được đổi, có CSRF và PRG. Đăng nhập sai ghi tại `logs/php-error.log`, không dùng `data/login-error.log`.

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
| `api/yeu-thich.php` | GET | Yêu thích của tài khoản đang đăng nhập |
| `api/yeu-thich.php` | POST | Thêm/bỏ yêu thích, yêu cầu đăng nhập và token CSRF |

Phản hồi có `Content-Type: application/json; charset=utf-8`. Các mã: 200 thành công; 400 JSON/ID sai; 401 token giỏ thiếu/sai; 404 môn không có; 405 sai phương thức kèm Allow; 422 dữ liệu/số lượng sai; 500 lỗi máy chủ.

Danh sách tìm kiếm có debounce 350ms; thêm giỏ bằng fetch cập nhật số món và nút ngay. Giữ form GET/POST dự phòng khi tắt JavaScript. Kế hoạch giới hạn số lượng 1–5, không thêm trùng môn; giỏ tài khoản được lưu MySQL để khôi phục khi đăng nhập lại. API `api/yeu-thich.php` hỗ trợ GET/POST, yêu cầu đăng nhập và token CSRF cho POST.

Kiểm thử API bằng [api.http](api.http), đổi `@goc` theo cách chạy. Ảnh và kết quả kiểm thử của các thành viên lưu tại `kiemtra/`; số liệu Bảng 2–5 và ERD được tổng hợp trong báo cáo chung.

Liên hệ đã đọc/ghi MySQL; bộ cài bổ sung `so_dien_thoai` và `uu_tien` cho bảng cũ mà không xóa liên hệ. ERD mô tả 24 bảng và 25 khóa ngoại, có `tv_du_an` của Trúc và liên kết thời tiết tới thành viên. Bảng thời tiết cũ được bổ sung `mssv`, gắn các lượt chưa có mã với Duy (`3120224034`), giữ ID/trạng thái/thời gian; thống kê luôn lọc theo MSSV. Chỉ ghi tính năng đã có và kết quả đã đo khi chốt bài.

Phân công Bài 6: Trúc phụ trách chức năng 1; Uyên chức năng 2 và 4; Hải chức năng 3; Duy chức năng 5; Hợp chức năng 6.

Trang Trúc có danh sách dự án đọc từ `tv_du_an`, tìm theo tên/công nghệ và sắp xếp bằng form GET, dùng được khi tắt JavaScript. GPA lưu kết quả vào `tv_truc_gpa`; quiz lưu từng lần trả lời và thống kê từ `tv_truc_quiz`. Cả hai bảng có MSSV và khóa ngoại tới thành viên. Dữ liệu tách theo tài khoản đăng nhập (dùng được trên máy khác); khách chưa đăng nhập có định danh riêng trong phiên, hết phiên sẽ không truy xuất lại kết quả khách cũ. Session chỉ giữ định danh khách, câu hỏi đang làm, CSRF và thông báo lỗi. Reset quiz chỉ xóa lịch sử trong phạm vi người đang xem; không xóa kết quả người khác. Chạy bộ cài sau khi lấy code mới để bổ sung hai bảng, không tạo kết quả mẫu.

GPA lưu một gói đầu vào và kết quả trong cột MySQL `tv_truc_gpa.ket_qua` kiểu JSON để đọc lại nguyên gói, không dùng tệp JSON riêng. PHP kiểm tra đầu vào/tính toán; kiểu JSON không thay thế ràng buộc nghiệp vụ từng trường và không đủ để tự chứng nhận toàn bộ thiết kế đạt 3NF. Giải thích và đánh đổi để đưa vào báo cáo nằm ở [mục 3.1 của bản đánh giá](docs/danh-gia-bai-6-20261027.md#31-giải-thích-lựa-chọn-json-cho-kết-quả-gpa).

### Trang cá nhân Huỳnh Phương Uyên

`thanhvien/3120224174_uyen/gioithieu.php` dùng `KhoTrangCaNhanUyen` để đọc kỹ năng từ `tv_ky_nang` và tìm kiếm thời khóa biểu trong `tv_thoi_khoa_bieu`. Hai bảng có khóa ngoại tới `thanh_vien`; cấu trúc nằm trong `database/schema.sql`, dữ liệu mẫu nằm trong `database/seed.sql` và bộ cài bổ sung idempotent khi nâng cấp CSDL cũ. Sau mỗi lần cập nhật, chạy `php database/cai-dat.php`, sau đó thử lọc kỹ năng, tìm môn theo tên/mã/lớp/phòng và thử lại khi tắt JavaScript. Không import schema thủ công vào CSDL đang có dữ liệu vì tệp này có lệnh `DROP DATABASE`.

Máy còn bảng `tv_uyen_ky_nang` / `tv_uyen_thoi_khoa_bieu`: bộ cài sao lưu rồi đổi sang tên mới trước khi tạo bảng còn thiếu, giữ ID, dữ liệu riêng và khóa ngoại. Chạy lại không tạo bảng trùng. Nếu cả tên cũ và tên mới cùng tồn tại cho một bảng, bộ cài dừng và báo hai tên để đối chiếu; không tự gộp, ghi đè hoặc xóa dữ liệu. Không xóa bảng bằng tay chỉ để vượt thông báo này. Kiểm tra trên CSDL riêng bằng `php kiemtra/kiem-thu-doi-ten-bang-uyen.php`.

### Vá và kiểm tra bản chốt ngày 27/10/2026

Ảnh môn học dùng được với cả tên tệp seed và đường dẫn uploads/ ở HTML/fetch. Trang Duy yêu cầu CSRF cho ba form POST; danh sách món tự nhập của khách giữ riêng trong session. Chỉ tài khoản liên kết với MSSV Duy qua thanh_vien.nguoi_dung_id mới lưu thêm món vào danh sách chung; không tự gán quyền admin làm chủ trang. Seed cập nhật đã có tài khoản `duy` và liên kết chủ Duy; bộ cài bổ sung liên kết còn trống trên máy cũ theo username/email, không dựa vào ID cố định và không ghi đè chủ đã có.

Oẳn tù tì vẫn lưu lịch sử/thống kê trong `tv_lich_su_ott`; check-in thời tiết vẫn lưu/thống kê trong `tv_thoi_tiet_duy`. Hai thao tác này yêu cầu token CSRF và dữ liệu hợp lệ, không yêu cầu quyền chủ trang. Quyền chủ Duy chỉ giới hạn thao tác lưu thêm món chung.

Bản vá đã được commit tại `ed2448970d3bffe10b23a0715365bef7a04e18be`. Ngày 27/10/2026, đối chiếu HEAD local `main` và `git ls-remote origin refs/heads/main` cùng ở `83124f39ec2ff02137bde3399639a112898acc51` (commit kế tiếp dọn minh chứng HTML trùng). Đây là mốc mã nguồn đã có trên remote, chưa phải xác nhận PDF/ZIP cuối đã nộp; phần cập nhật tài liệu trong lượt này chưa commit/push.

Minh chứng đo ngày 27/10/2026: [ảnh và JSON gốc](kiemtra/ban-chot-20261027/). Ba trang bắt buộc đạt Lighthouse Mobile Accessibility 100; Nu Html Checker cục bộ đo 16 trang/trạng thái có 0 lỗi HTML, một cảnh báo heading ở chi tiết. Đây là số đo lịch sử của các trang/trạng thái đã thử; không dùng số liệu này để khẳng định mọi ca kiểm thử đều đã hoàn tất.
