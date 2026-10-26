# Đánh giá mã nguồn theo đề Bài tập nhóm số 6

**Cập nhật sau vá:** các vấn đề 5.1–5.4 đã được xử lý và đo lại trong [biên bản bản chốt](../kiemtra/ket-qua-va-ban-chot-20261027.md). Phần bên dưới giữ nguyên kết quả đánh giá trước vá, không dùng nó thay trạng thái mới.

Ngày đánh giá: 27/10/2026, giờ Việt Nam. Mã nguồn được kiểm tra: `5b20cc814369ea24eb0a252273ffe84c13c9b36d`, working tree sạch trước khi rà soát. Đề đối chiếu: `BaiTapNhom6_Chuong6 (1).docx` do người dùng cung cấp. Phân công áp dụng theo lời chốt của người dùng, không theo các phần việc cũ trong ảnh.

Ứng dụng đã có đủ sáu nhóm chức năng bắt buộc và nền tảng CSDL chạy được từ schema/seed. Tuy nhiên, chưa đủ căn cứ để kết luận hoàn thành 100% bài nộp: đã tái hiện lỗi ảnh trong tìm kiếm JavaScript; còn điểm yếu ở thao tác ghi công khai của trang Duy; chưa đo lại chất lượng giao diện và chưa có bộ báo cáo PDF cuối để đối chiếu. Lượt này chỉ tạo báo cáo, không sửa mã ứng dụng, không commit/push.

## 1 Phạm vi và cách kiểm tra

- Đọc phần A, B, C, rubric và quy cách nộp trong đề; rà soát các trang PHP, lớp dữ liệu, dịch vụ, API, JavaScript, schema/seed và tài liệu kiểm thử liên quan.
- Kiểm tra cú pháp 76 tệp PHP ngoài vendor và thư mục đính kèm.
- Chạy lại 9 bộ kiểm thử PHP có sẵn, đều kết thúc với mã thoát 0.
- Xuất bản sao từ `git archive HEAD`, dùng autoload của thư mục vendor hiện có, đổi tên CSDL trong bản sao; tạo CSDL ngẫu nhiên chỉ từ schema.sql và seed.sql. Không chạy bộ cài để bù bảng cho bản thử HTTP.
- Thử HTTP bằng các cookie jar riêng cho khách, khách hàng và quản trị; chạy thêm bộ kiểm thử CSRF giỏ bằng Python. Các CSDL thử và tiến trình PHP do lượt này tạo đã được dọn.
- Chưa chạy lại `composer install` trong bản sao, chưa đo bằng trình duyệt/Lighthouse/W3C, chưa kiểm tra hiển thị thật với JavaScript bật/tắt, chưa xác minh GitHub từ xa. HTTP POST form thành công chỉ chứng minh đường xử lý dự phòng phía máy chủ.
- Các bản sao và log HTTP tạm đã được dọn theo yêu cầu người dùng; kết quả tổng hợp giữ trong tài liệu này và biên bản bản chốt. Một truy vấn phụ kiểm tra hash/tài khoản mới bị lỗi cú pháp trong script thử, không dùng kết quả đó để chứng nhận hash của tài khoản vừa đăng ký.

## 2 Phần A theo đúng phân công

| Chức năng | Người phụ trách | Mã nguồn chính | Kết quả và giới hạn |
| --- | --- | --- | --- |
| 1 Đọc dữ liệu, giỏ, liên hệ | Trúc | index.php, chi-tiet.php, gio-hang.php, lien-he.php; KhoMonHoc, KhoLienHe, KeHoachHocTap | Có MySQL/PDO; chi tiết JOIN tên danh mục; tối đa bốn môn cùng danh mục; môn mới theo ngày; đã xem và giỏ lấy theo IN. Bộ thử môn mới/liên quan và đã xem đạt. Liên hệ đã dùng lớp PDO, kiểm tra dữ liệu/ảnh và PRG; bộ thử cài đặt đọc/ghi liên hệ thành công. Chưa chạy lại toàn bộ luồng upload liên hệ bằng HTTP trong lượt này. |
| 2 Tìm, lọc, sắp xếp, phân trang | Uyên | danh-sach.php, KhoMonHoc | LIKE có escape ký tự %, _, dấu gạch chéo; WHERE/tham số dùng chung; ORDER BY theo danh sách cho phép; COUNT/LIMIT/OFFSET; giữ tham số bằng http_build_query. Tìm “lập trình”, “LAP TRINH”, “lap trinh” cùng 6 kết quả. Các tham số trang/sắp xếp sai và dạng mảng đã thử trả 200, không phát sinh lỗi PHP quan sát được. Chưa kiểm lại riêng đối chiếu COUNT và không lặp/sót giữa hai trang bằng HTTP. |
| 3 Quản trị môn và liên hệ | Hải | quan-tri.php, quan-tri-mon-hoc.php, xoa-mon-hoc.php; KhoQuanTriMonHoc, KiemTraMonHoc, TaiAnh | Có quyền quản trị, form thêm/sửa chung, điền dữ liệu cũ, phân trang, xác nhận xóa bằng form, POST/CSRF, lỗi từng trường, upload, PRG và xử lý 1062/1451. HTTP dữ liệu sai trả 422; khách vào quản trị trả 403; GET xóa trả 405; upload/thêm hợp lệ trả 303. Nhánh xóa môn tham chiếu trả 303 với xử lý lỗi trong source; bộ kiểm thử FK xác nhận bị chặn. Không bắt buộc tách thành them/sua-san-pham.php vì đề cho đổi tên tệp. |
| 4 Đăng ký, đăng nhập, đăng xuất | Uyên | dang-ky.php, dang-nhap.php, dang-xuat.php, inc/xac-thuc.php; KhoNguoiDung, KhoPhienGhiNho | Có password_hash/verify/needs_rehash, UNIQUE và bắt 1062, không lấy vai trò từ form, thông báo đăng nhập chung, session_regenerate_id, đọc quyền từ CSDL bằng uid. HTTP email/mật khẩu sai hiện lỗi; email trùng viết hoa bị nhận diện; đăng nhập đúng đổi PHPSESSID. Có ghi nhớ và đặt lại mật khẩu. Bộ thử xác thực đạt; chưa thử gửi email khôi phục thật. |
| 5 Gửi và quản lý kế hoạch | Duy | dat-hang.php, don-hang.php, KhoKeHoach | Đổi đơn hàng thành kế hoạch học tập là phù hợp đề. Có transaction, UPDATE số chỗ với điều kiện và rowCount, rollback, snapshot môn/tín chỉ, JOIN/GROUP BY/SUM, lọc chủ sở hữu và danh sách trạng thái. Bộ thử thành công, rollback môn thứ hai, giữ snapshot và hai tiến trình tranh chỗ cuối đều đạt. HTTP gửi kế hoạch trả 303 tới chi tiết; xem kế hoạch của tài khoản khác trả 404. Chưa thực hiện demo cạnh tranh bằng hai trình duyệt. |
| 6 REST API và fetch | Hợp | api/san-pham.php, api/gio-hang.php, inc/api.php, js/tim-kiem.js, js/gio-hang.js, MonHoc | JSON, trạng thái HTTP, php://input, JsonSerializable và bộ xử lý 500 đã có; mã dùng debounce 350 ms và textContent. Form dự phòng và CSRF giỏ đã chạy lại thành công. Có lỗi đường dẫn ảnh upload khi dựng kết quả tìm kiếm bằng JS, cần sửa trước khi chốt. Chưa đo lại một request cho cả từ khóa và cập nhật header không tải trang bằng trình duyệt. |

Các hàm ghi theo từng dòng chi tiết trong giao dịch không được đánh đồng với lỗi SELECT N+1. Khối giỏ/đã xem đã lấy theo IN; vòng lặp UPDATE/INSERT trong giao dịch có nhiệm vụ xử lý số chỗ và từng chi tiết.

## 3 CSDL và tổ chức mã

| Yêu cầu | Bằng chứng hiện tại | Đánh giá |
| --- | --- | --- |
| Tạo mới từ schema/seed | CSDL thử tạo được 24 bảng, 25 khóa ngoại, 30 môn | Đạt trong môi trường hiện tại |
| Danh mục | Phân bố môn: 7, 8, 9, 6, 0 trên năm danh mục | Đạt ít nhất bốn danh mục có dữ liệu và một danh mục rỗng |
| Mẫu đặc biệt | SEC401 hết chỗ; LOG401 còn một chỗ; tên INT304 chứa nháy đơn | Đạt yêu cầu dữ liệu đặc biệt; tìm đúng chuỗi nháy trả một kết quả |
| Tài khoản/đơn mẫu | Seed có admin và ba khách; uid 2, 3, 4 đều có kế hoạch | Đạt số lượng tối thiểu |
| Quan hệ | Danh mục–môn 1–n; chi tiết kế hoạch khóa ghép; FK môn RESTRICT | Có cơ sở đáp ứng mô hình bắt buộc |
| PDO | db() có utf8mb4, exception, fetch assoc, tắt emulate prepare | Đạt đối chiếu mã |
| Lớp truy cập dữ liệu | SQL nghiệp vụ tập trung trong src/Data, nhận PDO | Có nền tảng đúng; không thấy dùng mysqli/ORM trong luồng đã rà soát |
| Tệp dữ liệu cũ | git ls-files không còn JSON/JSONL nghiệp vụ trong data/storage; máy local còn các tệp runtime cũ | Không coi tệp local còn tồn tại là ứng dụng vẫn đọc JSON; nên loại runtime khi đóng ZIP |
| ERD | Có database/erd.png, DOT, SVG và tài liệu | Đã có; lượt này chưa kiểm tra hình và từng cạnh trên ảnh, không chứng nhận ERD khớp hoàn toàn |
| Quy ước tên | Có ngoại lệ như ke_hoach_id thay vì ke_hoach_hoc_tap_id; thanh_vien dùng mssv theo gợi ý Phần B | Nên giải thích ngoại lệ nhất quán trong Bảng 2; không đổi hàng loạt chỉ để chỉnh tên khi chưa cân nhắc migration |
| Chuẩn hóa | Snapshot được lưu trong chi tiết; GPA lưu đối tượng JSON trong một cột | Snapshot hợp lý theo đề. Nếu tuyên bố toàn bộ đạt 3NF, phải giải thích cấu trúc JSON GPA; không tự chứng nhận 3NF chỉ bằng số bảng/FK |
| Cấu hình lỗi | MOI_TRUONG='dev'; trang 500 không nạp header/CSDL; API handler riêng | Đúng yêu cầu source; mô phỏng prod mất CSDL trả HTML 500 và JSON 500, không lộ SQL/tài khoản |

## 4 Phần B từng thành viên

| Thành viên | Bảng và chức năng thực tế | Đánh giá |
| --- | --- | --- |
| Trúc | tv_du_an: danh sách/tìm/sắp xếp dự án; tv_truc_gpa: lưu và lấy kết quả GPA; tv_truc_quiz: lưu trả lời, thống kê/reset theo phạm vi người xem | Có hơn hai chức năng dùng CSDL. Bộ thử GPA/quiz đạt, tách phạm vi và reset không ảnh hưởng người khác. Không còn đúng nếu nói GPA/quiz chỉ dùng session. Dự án hiện là đọc/tìm/sắp xếp, không thấy giao diện thêm/sửa dự án như bảng ảnh mô tả. |
| Uyên | tv_uyen_ky_nang: đọc/lọc kỹ năng; tv_uyen_thoi_khoa_bieu: tìm môn/lớp/phòng trong lịch học | Có hai chức năng CSDL khác nhau và FK tới thành viên; không cần biến lọc và sắp xếp cùng danh sách thành hai chức năng. Trang và dữ liệu mẫu chạy được. Không mô tả là thêm kỹ năng nếu chưa có thao tác đó trong source. |
| Hải | tv_anh_dai_dien: lưu avatar và chỉ chủ trang được đổi; tv_luot_xem: đếm theo ngày/thống kê bảy ngày | Đủ hai nhóm chức năng CSDL; bộ thử chủ trang đạt, trang HTTP 200. Cần ảnh minh chứng trên bản chốt. |
| Duy | tv_mon_an: lưu/chọn món; tv_lich_su_ott: lịch sử/thống kê oẳn tù tì; tv_thoi_tiet_duy: check-in/thống kê | Có ít nhất hai chức năng; bảng thời tiết hiện đã có mssv/FK và kiểm thử migration đạt. Tuy nhiên danh sách món dùng chung nhận ghi từ khách không CSRF; cần gia cố và làm rõ ai được quyền sửa dữ liệu dùng chung. |
| Hợp | tv_binh_chon: mỗi tài khoản một phiếu, thống kê; tv_tra_cuu_mang: lưu và xem lịch sử tra cứu | Có hai nhóm chức năng CSDL. Bình chọn kiểm tra uid, CSRF và UNIQUE; tra cứu có kiểm tra dữ liệu và PRG. Không tính bộ kiểm tra độ mạnh mật khẩu là một chức năng CSDL. Lượt này chưa chạy lại HTTP bình chọn mới/trùng và tra cứu mạng. |

Ảnh phân công có phần việc cũ khác code: Duy được ghi sổ lưu bút, nhưng trang hiện thực tế làm món ăn/oẳn tù tì/thời tiết; một số mục “thêm” dự án/kỹ năng chưa có giao diện tương ứng. Nên cập nhật phụ lục theo chức năng đã làm, đồng thời giữ phân công Phần A đúng lời chốt: Trúc 1; Uyên 2 và 4; Hải 3; Duy 5; Hợp 6.

Lịch sử local `git shortlog -sne btn5..HEAD` có đủ các nhóm tên tác giả: Trúc 16; Uyên 12+1; Hải 6; Duy 7+5; Hợp 27+1. Đây là số commit theo tên/email tác giả trong Git local, không phải tỷ lệ đóng góp hoặc xác minh tài khoản GitHub. Không dùng số commit để chứng minh mọi người đều tự viết/hiểu toàn bộ phần được giao.

## 5 Vấn đề cần xử lý trước khi chốt

### 5.1 Ảnh upload bị sai đường dẫn trong kết quả tìm kiếm JavaScript

Vị trí: js/tim-kiem.js dòng 26–27. Hàm card chỉ nhận sẵn đường dẫn bắt đầu images/, URL http(s) hoặc dấu /. Khi API trả uploads/anh_....png, mã tự thêm images/ phía trước.

Đã tái hiện trên bản sao: đăng nhập admin, thêm môn kèm PNG thật trả 303; API tìm môn trả ảnh uploads/anh_....png; URL đúng trả 200; URL mà JS dựng images/uploads/anh_....png trả 404. Đây là sai khác giữa hiển thị server và kết quả dựng lại bằng fetch.

Hướng sửa: chuẩn hóa đường dẫn ảnh dùng chung, nhận cả images/ và uploads/, đồng thời giữ kiểm tra cùng nguồn. Sau sửa, thử cả ảnh seed và ảnh upload khi tải trang đầu, tìm kiếm và phân trang. Người liên quan: Hợp phối hợp Hải; không quy lỗi tác giả chỉ từ phân công.

### 5.2 Dữ liệu món ăn dùng chung cho phép khách ghi mà không có CSRF

Vị trí: thanhvien/3120224034_Duy/gioithieu.php nhánh chon_mon, gọi KhoCaNhan::luuDanhSachMon với MSSV cố định. Nhánh POST không có kiểm tra token hoặc chủ trang; hai nhánh oẳn tù tì/thời tiết cũng không kiểm tra token.

Đã tái hiện: một cookie jar khách gửi hai món mới, không đăng nhập/không token, nhận 303. Một khách mới mở trang nhìn thấy hai món đó, chứng minh dữ liệu được ghi vào tập dùng chung.

Đánh giá: đây là điểm yếu bảo vệ thao tác ghi; đề không liệt kê CSRF thành một ca bắt buộc cho tất cả biểu mẫu, nên không tự quy thành trượt toàn bộ Phần B. Nếu thêm món là thao tác công khai có chủ đích thì cần ghi rõ và chống gửi giả mạo/spam; nếu là chỉnh danh sách của chủ trang thì cần kiểm tra quyền chủ trang ở máy chủ. Chơi game/check-in có thể vẫn công khai nhưng nên có CSRF. Người phụ trách: Duy.

### 5.3 Tài liệu cũ chứa kết luận không còn đúng

docs/phan-c-cau-hoi-thao-luan.md có cảnh báo snapshot, nhưng thân tài liệu còn mô tả API hiện trả 500 vì thiếu soCho. Mã hiện tại đã có soCho và HTTP thêm hợp lệ trả 200. kiemtra/ra-soat-tu-86c7414-20261027.md có phần cập nhật ở đầu nhưng phía dưới vẫn còn các kết luận 22 bảng, GPA/quiz chỉ dùng session, thời tiết thiếu MSSV và lỗi CRUD gom đầu form.

Các nhận xét đó không được chép nguyên vào PDF như trạng thái hiện tại. Hợp tổng hợp cùng từng người phụ trách cần viết lại kết luận cuối, giữ số liệu lịch sử kèm commit rõ ràng. Không xóa bằng chứng lịch sử để làm đẹp kết quả.

### 5.4 Chú thích đầu tệp và minh chứng chất lượng

Phần B yêu cầu mỗi tệp có 3–5 dòng nói chức năng và cách thử. Ví dụ trang Duy hiện chỉ có dòng tên tệp ở phần chú thích đầu; KhoTrangCaNhanUyen/KhoTrangCaNhanTruc không có khối hướng dẫn thử đầu tệp. Cần rà lại thống nhất, đây là thiếu quy cách tài liệu, không phải lỗi thực thi PHP.

Ảnh W3C/Lighthouse các bài cũ hoặc trang cá nhân không chứng minh toàn bộ HTML mới đạt 0 lỗi hay ba trang bắt buộc đạt Mobile Accessibility >=90. Cần đo lại đúng bản chốt, lưu ảnh có chú thích, ghi kết quả vào Bảng 4/5.

## 6 Đối chiếu 14 ca Bảng 3

“Một phần” nghĩa là chưa thực hiện đủ tất cả bước trong ca đề bài, không đồng nghĩa ca đã thất bại.

| Ca | Trạng thái của lượt rà soát này |
| --- | --- |
| 1 Tìm kiếm và ký tự đặc biệt | Một phần: ba biến thể “lập trình” cùng 6 kết quả; tên có nháy khớp một môn; 50% và Levi's trả 0 vì seed không có chuỗi tương ứng. Chưa thêm dữ liệu có 50% để chứng minh khớp literal qua HTTP. |
| 2 Tham số sai | Đạt các biến thể tương đương đã thử: sx/trang/danh mục/tín chỉ sai, đầu vào mảng không làm hỏng trang. |
| 3 Giữ lọc và phân trang | Có source đúng; chưa kiểm lại toàn bộ COUNT và không lặp/sót giữa các trang. |
| 4 CRUD dữ liệu sai | HTTP nhiều trường sai trả 422; kiểm tra dùng chung và lỗi cạnh từng trường đã có. Chưa đối chiếu trực tiếp số dòng trước/sau mọi biến thể trong lượt này. |
| 5 Xóa môn có tham chiếu và GET | GET admin nhận 405; nhánh POST nhận 303. FK RESTRICT và bộ thử độc lập xác nhận chặn 1451. |
| 6 Đăng ký | Email/mật khẩu sai hiển thị lỗi; email trùng viết hoa bị bắt; đăng ký có vai_tro giả trả 303 và source luôn ghi khach_hang. Truy vấn phụ kiểm tra hash/tài khoản mới lỗi ở script thử, không đánh dấu toàn bộ ca đã kiểm chứng HTTP+CSDL. |
| 7 Đăng nhập | Hai trường hợp sai cùng thông báo; đúng đổi PHPSESSID và chuyển hướng. Chưa kiểm giao diện header bằng trình duyệt. |
| 8 Quyền | Chưa đăng nhập chuyển dang-nhap; khách vào quản trị 403; khách A xem kế hoạch B trả 404. |
| 9 Gửi kế hoạch | HTTP gửi thành công 303 tới chi tiết; bộ thử dữ liệu xác nhận chi tiết/snapshot/trừ chỗ. Chưa làm lại đủ ca hai món và F5 bằng trình duyệt. |
| 10 Rollback | Bộ thử số chỗ đạt: môn thứ hai thiếu chỗ rollback toàn bộ và giữ giỏ. Chưa lặp đủ thao tác bằng phpMyAdmin/trình duyệt. |
| 11 API tìm/chi tiết | 200 JSON ở tìm kiếm; ID không có 404; ID sai 400. |
| 12 API giỏ lỗi | Đã thử 400 JSON sai, 422 số lượng âm, 404 môn không có, 422 vượt chỗ, 405 DELETE có Allow; thiếu token 401; thêm đúng 200. Bộ thử CSRF xác nhận yêu cầu token lỗi không làm đổi giỏ. |
| 13 fetch và tắt JS | Form dự phòng và bộ thử CSRF đạt; mã có debounce/textContent. Chưa đo request/header thật bằng trình duyệt; đã phát hiện lỗi ảnh trong dữ liệu dựng lại bởi JS. |
| 14 Mất CSDL ở prod | Bản sao đổi prod và cổng DB không hoạt động: trang 500 thân thiện; API 500 application/json với loi=Lỗi máy chủ; không lộ SQLSTATE/PDOException/tài khoản/tên CSDL trong phản hồi. Không sửa config thật. |

## 7 Kết quả các bộ kiểm thử chạy lại

| Bộ thử | Kết quả |
| --- | --- |
| php -l, 76 tệp | Không lỗi cú pháp |
| kiem-thu-cai-dat.php | Đạt cài mới/nâng cấp/lặp/giữ dữ liệu/ánh xạ ID |
| kiem-thu-kho-xac-thuc.php | Đạt tài khoản, hash, phiên, thu hồi và rollback đặt lại mật khẩu |
| kiem-thu-so-cho.php | Đạt schema/seed, snapshot, rollback, hai tiến trình tranh chỗ |
| kiem-thu-mon-moi-lien-quan.php | Đạt JOIN danh mục, môn mới, tối đa bốn môn liên quan |
| kiem-thu-da-xem.php | Đạt thứ tự, cookie lỗi/trùng/mảng, lấy bằng một truy vấn khi có ID hợp lệ |
| kiem-thu-chu-trang-hai.php | Đạt đúng/sai/chưa có chủ và bảo toàn liên kết |
| kiem-thu-chon-mon-duy.php | Đạt kiểm tra danh sách, giới hạn và không ghi đầu vào sai |
| kiem-thu-thoi-tiet-duy.php | Đạt migration, MSSV/FK, lọc đúng thành viên |
| kiem-thu-gpa-quiz-truc.php | Đạt lưu/đọc, thống kê, reset có phạm vi, FK, nâng cấp |
| kiem-thu-csrf-gio-hang.py | Đạt thao tác thêm/sửa/xóa/xóa hết, form và URL cũ |

Trong các lượt HTTP nghiệp vụ trước mô phỏng lỗi CSDL, không thấy Warning/Notice/Deprecated/Fatal trong log PHP bản thử. Mô phỏng mất CSDL có log ngoại lệ chủ đích; không gộp log đó thành lỗi PHP phát sinh ở nghiệp vụ bình thường. Kết quả này không chứng minh mọi đường đi chưa thử đều không có lỗi.

## 8 Phần C và bộ nộp

Phần C đã có tài liệu trả lời đủ bốn chủ đề, mã thử và log: prepared statement/ORDER BY, transaction/cạnh tranh/snapshot, EXPLAIN/N+1/phân trang sâu, hash/quyền/API. Cần cập nhật tham chiếu dòng và lời kết luận theo commit chốt, đưa kết quả cần thiết thành bảng/câu văn. Không trình bày thí nghiệm hai tiến trình như minh chứng đã thử hai trình duyệt.

Trong phạm vi kho đã kiểm tra chưa thấy PDF cuối đúng tên Nhom03_Baitap6.pdf hoặc ZIP nộp LTW_BTN6_Nhom03_code.zip; tệp ZIP ảnh kiểm thử cũ không phải ZIP mã nguồn đúng quy cách. Không kết luận các tệp này không tồn tại ở nơi khác trên máy.

Checklist để chốt bài:

1. Hợp xử lý đường dẫn ảnh JS; Duy làm rõ quyền và bảo vệ các POST trang cá nhân; bổ sung chú thích còn thiếu.
2. Đo W3C HTML được PHP sinh ra, không cuộn ngang 360px, Lighthouse Mobile Accessibility >=90 cho index/danh-sach/dang-ky và các trang cần ghi Bảng 4/5.
3. Chốt Bảng 1 theo phân công hiện tại; Bảng 2 đủ 24 bảng, khóa/ràng buộc/số dòng; Bảng 3 đủ 14 ca; Bảng 4/5 điền theo phép đo và minh chứng thực tế.
4. Đồng bộ Phần C, ảnh ERD và biên bản đánh giá; không giữ các nhận xét lịch sử như kết luận hiện tại.
5. PDF có đúng cấu trúc đề, mã sinh viên/GitHub/commit, ít nhất ba tài liệu tham khảo đáng tin cậy, khai báo dùng AI và phụ lục phân công.
6. Chạy `git diff --stat btn5 <commit-chot>` để đưa vào báo cáo. Hiện tại btn5..HEAD là 166 files changed, 8258 insertions, 3058 deletions; số này sẽ đổi khi chốt commit khác.
7. Kiểm tra tag btn5 và lịch sử từng người trên remote; local có tag không tự chứng minh đã push tag.
8. Tạo ZIP đúng tên, bỏ .git và vendor; loại runtime, log, bản sao SQL/dữ liệu cá nhân trước khi nộp. Thử tải đúng commit cuối, composer install, import schema/seed và chạy theo README một lần cuối.

Không quy đổi thành điểm số chắc chắn: rubric A 5 điểm, B 2 điểm, C 2 điểm, D 1 điểm còn phụ thuộc bộ PDF, minh chứng chất lượng, lịch sử đóng góp và chạy lại của giảng viên. Kết luận phù hợp hiện tại là nền tảng và phần lớn nghiệp vụ đã chạy được, còn lỗi cụ thể cùng phần kiểm chứng/hồ sơ cần hoàn tất.
