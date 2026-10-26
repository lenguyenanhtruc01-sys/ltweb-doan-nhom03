# Kết quả vá và kiểm tra Bài 6 ngày 27 tháng 10 năm 2026

**Bổ sung bộ tài khoản theo yêu cầu:** seed hiện dùng admin, hop, uyen, duy, hai; bốn tài khoản thành viên có email tương ứng `@example.com`. Đã kiểm thử cài mới, cài dở, nâng cấp, ID khác seed, giữ chủ/mật khẩu cũ và chạy lại không trùng trên CSDL riêng; các bộ xác thực/chủ Hải và PHP lint cũng đạt. Sau đó đã chạy bộ cài trên CSDL local có sao lưu: tạo thêm hop, uyen, duy, gắn chủ Duy còn trống và giữ chủ Hải. Đối chiếu local xác nhận cả bốn tài khoản thành viên có mật khẩu `matkhau123`, hai liên kết chủ đúng. Tài khoản an/binh cũ và lịch sử được giữ; seed cài mới không còn hai tài khoản đó. Các kiểm tra này không phải chạy lại toàn bộ browser/Lighthouse; thay đổi bộ tài khoản chưa commit/push.

Đã sửa đường dẫn ảnh ở HTML và fetch; bảo vệ ba POST trang Duy bằng CSRF; chỉ chủ trang được lưu thêm món dùng chung; bổ sung chú thích đầu tệp và cập nhật các kết luận tài liệu đã cũ. Bản vá từ mốc `5b20cc814369ea24eb0a252273ffe84c13c9b36d` đã được ghi trong commit `ed2448970d3bffe10b23a0715365bef7a04e18be` (`va loi`). Commit tiếp theo `83124f39ec2ff02137bde3399639a112898acc51` (`delete`) dọn ba HTML Lighthouse trùng và cập nhật tài liệu. Đối chiếu ngày 27/10/2026: HEAD local trên `main` và đầu nhánh `main` trên origin cùng là `83124f39ec2ff02137bde3399639a112898acc51` (`git ls-remote origin refs/heads/main`); bản vá đã có trên remote, không còn ở trạng thái chưa commit/push. Đây là mốc mã nguồn đối chiếu, chưa phải xác nhận PDF/ZIP cuối đã nộp.

Các kết quả browser/Lighthouse và bảng đo bên dưới được giữ từ lượt vá ban đầu. Lượt đồng bộ tài liệu trước chỉ đối chiếu mã nguồn, schema/seed và lịch sử Git; phần kiểm thử CSDL và áp dụng bộ tài khoản mới được ghi riêng ở đầu biên bản. Các thay đổi trong working tree hiện chưa commit/push.

## Thay đổi và hành vi

- `inc/ham.php::duongDanAnhMonHoc()` dùng chung ở danh sách và chi tiết: tên ảnh seed được thêm images/, ảnh uploads/ giữ nguyên. `js/tim-kiem.js` nhận cả hai tiền tố, giữ chặn URL khác nguồn.
- Trang Duy dùng token CSRF chung có sẵn, kiểm tra kiểu và token trước xử lý mọi POST. Thiếu/sai/token dạng mảng trả 403, không ghi dữ liệu.
- Khách vẫn chọn món từ danh sách tự nhập; danh sách đó ở session của khách. Chỉ tài khoản được liên kết với MSSV Duy trong `thanh_vien.nguoi_dung_id` mới lưu thêm món vào MySQL. Vai trò admin tự nó không cấp quyền chủ trang. Không nhận UID/MSSV do form gửi để xác định chủ.
- Oẳn tù tì và check-in vẫn công khai với token hợp lệ; POST thành công dùng PRG 303. CSRF không thay thế biện pháp chống spam tự động.
- Không tự tạo tài khoản Duy, gán chủ trang hoặc thay dữ liệu CSDL thật. Ở mốc bản vá đã push, seed chưa liên kết tài khoản cho Duy. Seed cập nhật trong working tree đã thêm tài khoản duy và liên kết chủ Duy; bộ cài chỉ bổ sung chủ còn trống theo username/email, không ghi đè chủ hiện có. Khách vẫn dùng đầy đủ chức năng chọn món cá nhân.
- Chú thích chức năng/cách thử đã đủ ở năm trang cá nhân và các lớp dữ liệu cá nhân được rà soát.

## Giải thích GPA lưu JSON trong MySQL

`database/schema.sql` khai báo bảng `tv_truc_gpa` gồm `id`, `mssv`, `pham_vi`, `ket_qua JSON` và `ngay_tao`; MSSV có khóa ngoại tới `thanh_vien`. `KhoTrangCaNhanTruc::luuGpa()` mã hóa mảng kết quả bằng `json_encode(..., JSON_THROW_ON_ERROR)` rồi INSERT qua PDO. `layGpa()` lọc theo MSSV và phạm vi người xem, lấy bản ghi có ID mới nhất và giải mã JSON để hiển thị. Đây là dữ liệu lưu trong CSDL, không phải cơ chế đọc/ghi tệp JSON cũ.

Đơn vị lưu là một lần tính chi phí/GPA: gói đầu vào và kết quả được lưu cùng nhau để đọc lại sau chuyển hướng hoặc khi tải lại trang. Cách đóng gói này phù hợp luồng hiện tại vì ứng dụng lấy lại toàn bộ kết quả; chưa có truy vấn SQL lọc hoặc tổng hợp riêng từng trường bên trong JSON. Phạm vi người xem được máy chủ tạo từ tài khoản đăng nhập hoặc định danh khách trong session, không nhận trực tiếp từ form.

Đánh đổi: kiểu JSON kiểm soát cú pháp tài liệu nhưng không tự áp đặt đầy đủ kiểu, miền giá trị và quan hệ giữa các trường GPA; việc kiểm tra đầu vào và tính toán nằm ở PHP. Các giá trị tính ra được lưu cùng đầu vào, nên khi thay đổi công thức phải phân biệt kết quả lịch sử với kết quả tính mới. Không dùng thiết kế này để tuyên bố toàn bộ dữ liệu bên trong JSON đã được chuẩn hóa quan hệ tới 3NF. Nếu cần báo cáo SQL theo GPA/tín chỉ/học phí, ràng buộc từng trường hoặc truy vấn thường xuyên, nên cân nhắc tách thành các cột có kiểu và ràng buộc phù hợp. Đây là giải thích giới hạn thiết kế hiện tại, không phải một migration đã thực hiện.

## Quyền và thao tác CSDL của trang Duy

Ba POST đều qua kiểm tra CSRF trước xử lý. Riêng việc thêm món dùng chung còn yêu cầu tài khoản đăng nhập khớp `thanh_vien.nguoi_dung_id` của MSSV `3120224034`, do `KhoCaNhan::laChuTrang()` kiểm tra. Seed mới có duy/duy@example.com và gắn chủ Duy; tài khoản admin không mặc nhiên được quyền này. Bộ cài nâng cấp bổ sung tài khoản thiếu và gắn chủ còn trống theo username/email, không chép ID cố định lên dữ liệu cũ. Khi đã có chủ hoặc tài khoản khác seed, cần đối chiếu dữ liệu thực tế, không ghi đè. Lượt đồng bộ tài liệu trước không tự tạo hoặc gán tài khoản trong CSDL thật; lần cập nhật bộ tài khoản theo yêu cầu sau đó đã áp dụng trên local như ghi ở đầu biên bản.

Giới hạn trên không làm mất hai thao tác CSDL độc lập: oẳn tù tì ghi `tv_lich_su_ott` rồi đọc lịch sử/thống kê; check-in thời tiết ghi `tv_thoi_tiet_duy` rồi thống kê theo MSSV. Hai thao tác này vẫn công khai với đầu vào và token hợp lệ, không yêu cầu quyền chủ trang. Danh sách món riêng của khách chỉ lưu session; không lấy thao tác đó làm minh chứng khách được ghi vào bảng món chung.

## Phương pháp kiểm tra

Bản sao riêng tạo từ các tệp Git đang quản lý, phủ bản vá; CSDL ngẫu nhiên tạo từ schema.sql và seed.sql. Tài khoản, liên kết chủ trang, môn/ảnh upload thử chỉ được tạo trong bản sao/CSDL thử. Chạy Chrome headless bằng Playwright với profile riêng, không dùng phiên trình duyệt thật của người dùng.

Lighthouse 13.5.0 chạy Mobile, chỉ đo Accessibility, trên Chrome cài sẵn. Nu Html Checker 26.10.12 chạy cục bộ từ bản phân phối Windows chính thức: dịch vụ validator.w3.org/nu trực tuyến trả HTTP 403 nên không dùng nó làm minh chứng. Kiểm tra mã HTML phản hồi từ PHP, gồm các trang quản trị sau đăng nhập đúng vai trò; không coi HTML viết tay hoặc HTML của báo cáo Lighthouse là HTML ứng dụng.

Nguồn công cụ: [Lighthouse CLI chính thức](https://github.com/GoogleChrome/lighthouse), [Nu Html Checker chính thức](https://github.com/validator/validator). Không thêm các công cụ này vào dependency của website.

## Kiểm thử hành vi và hồi quy

| Kiểm tra | Kết quả |
| --- | --- |
| Node regression ảnh | Đỏ trước vá tại uploads/; xanh sau vá. Ảnh seed, uploads/, root-relative đúng; ảnh khác nguồn bị chặn |
| Thử bỏ kiểm tra origin | Test thất bại; khôi phục mã và test xanh |
| CSRF trang Duy | Đỏ trước vá vì POST thiếu token vẫn chạy; sau vá cả ba thao tác thiếu/sai/token mảng đều 403 |
| Đối chiếu CSDL sau POST sai token | Số dòng món ăn, lịch sử chơi, thời tiết không đổi |
| Khách có token đúng | Chọn món/chơi/check-in và PRG hoạt động; món riêng không hiện ở phiên khách khác |
| Người khác gửi thêm UID giả | Không lưu được món vào danh sách chung |
| Chủ trang được liên kết | Lưu món được; chỉ gán liên kết này trong CSDL thử |
| Chrome ảnh seed/upload | Đúng trên HTML và fetch, trang 1 và 2, bật/tắt JS; kiểm naturalWidth/decode và URL không có images/uploads/ |
| Upload thực tế qua quản trị | Thêm môn kèm tệp PNG thật thành công; ảnh hiện ở HTML và kết quả fetch |
| Debounce | Gõ nhanh “Audit image” tạo đúng một request api/san-pham.php |
| Thêm kế hoạch bằng fetch | Số trên header cập nhật thành 1, URL không đổi, thông báo xuất hiện |
| JavaScript runtime của ca hồi quy | Không có pageerror |
| PHP lint | 76 tệp ngoài vendor và thư mục đính kèm không lỗi cú pháp |
| 9 bộ kiểm thử PHP hiện có | Đều đạt, gồm cài đặt, xác thực, số chỗ, môn mới/liên quan, đã xem, chủ Hải, món Duy, thời tiết Duy, GPA/quiz Trúc |

Lệnh kiểm tra nhanh:

```powershell
node kiemtra/kiem-thu-anh-tim-kiem.cjs
php kiemtra/kiem-thu-chon-mon-duy.php
# Chỉ chạy trên bản sao/CSDL thử: có gửi các POST hợp lệ để chơi/check-in.
python kiemtra/kiem-thu-csrf-duy.py http://localhost:8000
```

## Bảng 4 kết quả đo chất lượng trang

Cột lỗi PHP là số Warning/Notice/Deprecated/Fatal quan sát được trong các lần tải và ca kiểm thử đã chạy, không phải chứng minh mọi tổ hợp dữ liệu đều không có lỗi. “Không JS” chỉ ghi rõ thao tác thực sự đã thử; chưa ghi đạt mọi thao tác của các trang chỉ được tải.

| Trang hoặc trạng thái | Lỗi PHP quan sát | Lỗi HTML Nu | Tràn ngang 360px | Lighthouse Mobile A11y | Không JS trong lượt này |
| --- | --- | --- | --- | --- | --- |
| index.php | 0 | 0 | Không, 360/360 | 100 | Chưa chạy lại toàn bộ thao tác |
| danh-sach.php | 0 | 0 | Không, 360/360 | 100 | Đã thử danh sách lọc/phân trang và ảnh trang 1/2 |
| chi-tiet.php?id=1 | 0 | 0, một cảnh báo heading | Không, 360/360 | Chưa đo | Form dự phòng đã qua bộ thử giỏ ở lượt đánh giá trước; chưa chạy lại toàn bộ ở lượt vá |
| gio-hang.php | 0 | 0 | Không, 360/360 | Chưa đo | Chưa chạy lại toàn bộ thao tác |
| lien-he.php | 0 | 0 | Không, 360/360 | Chưa đo | Chưa chạy lại toàn bộ thao tác |
| dang-ky.php | 0 | 0 | Không, 360/360 | 100 | Chưa chạy lại toàn bộ thao tác |
| dang-nhap.php | 0 | 0 | Không, 360/360 | Chưa đo | Đăng nhập bằng form đã chạy trong browser/HTTP |
| don-hang.php, tài khoản khách | 0 | 0 | Không, 360/360 | Chưa đo | Chưa chạy lại toàn bộ thao tác |
| quan-tri.php, admin | 0 | 0 | Không, 360/360 | Chưa đo | Chưa chạy lại toàn bộ thao tác |
| quan-tri-mon-hoc.php?sua=1, admin | 0 | 0 | Không, 360/360 | Chưa đo | Chưa chạy lại toàn bộ thao tác |
| quan-tri-mon-hoc.php, form thêm | 0 | 0 | Không, 360/360 | Chưa đo | Upload/thêm đã chạy bằng form trong browser |

Mỗi giá trị 360/360 là viewport và document.scrollWidth cùng bằng 360. Đây là phép đo chiều rộng ở trạng thái được nêu, không phải chứng nhận mọi menu/modal hoặc dữ liệu dài chưa thử.

## Bảng 5 minh chứng phần cá nhân

| Thành viên | Chức năng CSDL hiện có | Chức năng A | PHP quan sát | HTML Nu | 360px | Commit local btn5..5b20cc8 trước vá |
| --- | --- | --- | --- | --- | --- | --- |
| Trúc | Dự án; GPA (JSON trong MySQL); quiz | 1 | 0 | 0 | 360/360 | 16 |
| Uyên | Kỹ năng; thời khóa biểu | 2 và 4 | 0 | 0 | 360/360 | 13 theo hai tên tác giả |
| Hải | Avatar chủ trang; lượt xem bảy ngày | 3 | 0 | 0 | 360/360 | 6 |
| Duy | Món chung cần liên kết chủ trang; lịch sử chơi; thời tiết | 5 | 0 | 0 | 360/360 | 12 theo hai tên tác giả |
| Hợp | Bình chọn; lịch sử tra cứu mạng | 6 | 0 | 0 | 360/360 | 28 theo hai tên tác giả |

Số commit là thống kê local của lượt đánh giá trước trên cùng HEAD, không đo lại GitHub trong lượt vá và không phải tỷ lệ đóng góp. Các phép đo phần cá nhân là tải trang mặc định; không khẳng định mọi trạng thái sau gửi form đều đã được kiểm Nu riêng.

## Minh chứng và chú thích hình

Tất cả nằm trong [ban-chot-20261027](ban-chot-20261027/). JSON là số liệu gốc; PNG là minh họa để đưa vào PDF cùng chú thích. Ba báo cáo HTML Lighthouse tự sinh đã được dọn vì trùng nội dung với JSON/PNG:

- Hình 1–3: `lighthouse-index.png`, `lighthouse-danh-sach.png`, `lighthouse-dang-ky.png` — Accessibility 100, Mobile. Đối chiếu JSON tương ứng để xem cấu hình và phiên bản.
- Hình 4: `tim-kiem-upload.png` — ảnh seed và ảnh uploads trong kết quả fetch.
- Hình 5: `phan-trang-upload.png` — ảnh trang 2 vẫn đúng đường dẫn.
- Hình 6–7: `upload-that-html.png`, `upload-that-fetch.png` — PNG tải qua form quản trị, hiển thị ở HTML và fetch.
- Các tệp `*-360.png`: ảnh đầy trang của 16 trang/trạng thái đo; ảnh lazy-load có thể chưa tải các mục ngoài viewport ở thời điểm chụp. Không dùng ảnh đó để kết luận ảnh bị hỏng; ca hồi quy ảnh kiểm tra decode/naturalWidth sau cuộn tới ảnh.
- `nu-html.json`: 0 lỗi, một cảnh báo Article lacks heading ở chi tiết. Đây là cảnh báo, không được ghi thành “0 cảnh báo”.
- `widths.json`, `a11y-summary.json`, `browser-regression.json`, `security-regression.json`: số đo và kết quả đối chiếu.

## Giới hạn khi đưa vào báo cáo nộp

Chưa xác minh PDF/ZIP cuối, tag trên remote, demo cạnh tranh bằng hai trình duyệt, W3C trực tuyến hay mọi trạng thái lỗi động của từng trang. Các kết quả ở trên đủ để cập nhật những ô đã đo trong Bảng 4/5; các ô chưa đo phải giữ rõ trạng thái. Mã vá đã có trên `main` từ xa tại mốc đối chiếu `83124f3`, nhưng chưa có căn cứ gọi đó là commit cuối đã nộp. Báo cáo phải nêu JSON GPA là lựa chọn lưu gói kết quả trong MySQL và seed mới đã có chủ Duy; máy cũ cần chạy bộ cài và đối chiếu liên kết thực tế.
