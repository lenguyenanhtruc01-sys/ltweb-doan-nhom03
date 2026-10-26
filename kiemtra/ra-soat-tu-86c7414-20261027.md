> Cập nhật GPA/quiz Trúc (đã qua kiểm thử CSDL và HTTP: tính GPA, điểm quiz, F5, tách khách, reset, CSRF): kết quả đã lưu MySQL qua KhoTrangCaNhanTruc, tách người xem, CSRF và PRG; session chỉ giữ định danh/câu hỏi/thông báo. Hai bảng mới có MSSV/FK; ERD hiện 24 bảng, 25 khóa ngoại.

# Rà soát và vá hồi quy từ mốc code gốc ngày 27 tháng 10 năm 2026

Cập nhật tiếp cùng ngày: đã bổ sung MSSV/FK cho thời tiết Duy, cập nhật truy vấn và migration giữ lượt cũ; kiểm thử tại `kiemtra/kiem-thu-thoi-tiet-duy.php` đạt. ERD hiện 22 bảng/23 FK. Nhận xét bảng thời tiết thiếu liên kết ở bảng đánh giá ban đầu bên dưới đã được xử lý.

Cập nhật CRUD: lỗi kiểm tra trả theo tên trường, hiển thị ngay dưới ô tương ứng và có aria-invalid/aria-describedby. Mã môn trùng gắn vào ô mã, ảnh lỗi gắn vào ô ảnh; vẫn giữ tóm tắt đầu form có liên kết tới ô lỗi. Kiểm thử HTTP trên CSDL ngẫu nhiên: thêm/sửa với nhiều trường sai, trùng mã, mô tả quá dài, đầu vào mảng, tên có HTML và ảnh giả đều trả 422, không ghi dữ liệu; thêm/sửa hợp lệ vẫn thành công qua PRG. Mục “lỗi form gom đầu form” ở bảng đánh giá ban đầu đã được xử lý. Chưa đo lại W3C/Lighthouse cho giao diện mới.

Mốc người dùng chọn: `86c741440b87887512d5cb7e509bcccc5e3b8cd7`. Đối chiếu đến HEAD `67a731b` và origin/main đã fetch, sau đó sửa trong working tree; chưa stage/commit/push. Không thay toàn bộ code mới bằng bản cũ: giữ liên hệ PDO, cột điện thoại/ưu tiên, bảng dự án Trúc, lọc/sắp xếp mới và tài liệu/thí nghiệm Phần C.

## Lỗi đã vá

| Phần | Lỗi phát sinh | Bản vá và kết quả |
| --- | --- | --- |
| Schema | Bản sau mốc gốc xóa các bảng phiên/giỏ/yêu thích/avatar/Duy/Uyên; xóa so_cho, trạng thái, ghi chú, snapshot | Khôi phục phần nền từ mốc gốc và giữ cấu trúc mới. Schema hiện có 22 bảng, 22 FK, 30 môn trong seed; tạo mới chỉ bằng schema + seed chạy được. |
| Khóa ngoại môn học | CASCADE làm mất chi tiết kế hoạch khi xóa môn | Khôi phục RESTRICT; thử bản cài mới và nâng cấp từ CASCADE đều chặn xóa môn tham chiếu bằng lỗi 1451. |
| Model | MonHoc mất soCho nên thêm giỏ trả 500 | Khôi phục kiểu int/đọc so_cho; API thêm hợp lệ trả 200. |
| Form chi tiết | Xóa trường _csrf khiến thêm môn không JavaScript bị từ chối | Bổ sung token đúng loại giỏ; HTTP POST chạy được, token sai vẫn bị chặn. |
| Ảnh chi tiết | Seed lưu tên file nhưng src thiếu images/ | Chuẩn hóa tên file; giữ uploads/ và URL đã có. Ảnh seed trả 200. |
| Quản trị | Mất liên kết tới CRUD môn học | Khôi phục liên kết; vẫn giữ danh sách liên hệ MySQL mới. |
| Liên hệ PDO | Database cũ thiếu so_dien_thoai và uu_tien | Thêm migration trong bộ cài. Giữ liên hệ SQL cũ, bổ sung giá trị mặc định; không tự đọc/xóa JSONL cũ. |
| Dự án Trúc | Có bảng/lớp/query nhưng kết quả chưa được xuất ra trang; bộ cài chưa gộp seed | Gộp hai dự án theo MSSV/tên, tạo bảng khi thiếu; thêm giao diện dự án, form GET tìm/sắp xếp và trạng thái rỗng. Kiểm tra q dài/giá trị sx; in qua e(). |
| Tài liệu và thí nghiệm | README/ERD còn dữ liệu cũ, Phần C mô tả API 500 theo snapshot | Cập nhật README/phân công, ERD DOT/PNG/SVG 22 bảng. Giữ báo cáo/log cũ, thêm log ngày 27 và ghi rõ phạm vi lịch sử. Ca HTTP thành công trong bộ thử Phần C đặt lại tồn chỗ trước khi gửi. |

## Xác minh

- Kiểm tra cú pháp toàn bộ PHP ngoài vendor, không có lỗi.
- `php kiemtra/kiem-thu-cai-dat.php`: cài mới 22 bảng, seed đầy đủ, nâng cấp bảng/cột thiếu, giữ liên hệ cũ/mật khẩu/giỏ/kế hoạch, chạy lặp không tạo trùng, sửa FK CASCADE, ID khác seed. Tất cả đạt.
- `php kiemtra/kiem-thu-kho-xac-thuc.php`: tài khoản, hash, phiên hết hạn/thu hồi và rollback khi đổi mật khẩu lỗi; đạt.
- `php kiemtra/kiem-thu-chon-mon-duy.php`: danh sách món hiện tại, kiểm tra dữ liệu, không ghi sai; đạt.
- `php kiemtra/kiem-thu-chu-trang-hai.php`: đúng chủ/chưa có chủ/sai chủ/ID khác; đạt.
- `php kiemtra/kiem-thu-so-cho.php`: snapshot, rollback và hai tiến trình tranh chỗ cuối; đạt.
- `php kiemtra/kiem-thu-mon-moi-lien-quan.php`: JOIN danh mục, môn mới/liên quan; đạt.
- HTTP trên checkout tạm xuất từ HEAD rồi phủ đúng bản vá, `composer install`, tạo CSDL ngẫu nhiên chỉ bằng schema + seed: các trang chính và cả 5 trang cá nhân trả 200. Không cần bộ cài để bù thiếu bảng.
- HTTP tìm có dấu/không dấu/hoa cùng kết quả, API ID sai/không có/phương thức/dữ liệu mảng đúng 400/404/405/422.
- `python kiemtra/kiem-thu-csrf-gio-hang.py <URL bản thử>`: đủ thao tác/URL cũ, token sai không đổi giỏ, form đúng chạy được.
- HTTP liên hệ email quá dài/mảng bị từ chối; ảnh PNG thật tên audit.php lưu thành tên ngẫu nhiên .png, SQL có điện thoại/ưu tiên, quản trị đọc được; F5 không gửi lại.
- HTTP đăng nhập đổi session ID, khách bị chặn admin, xem kế hoạch người khác 404; thêm giỏ API và gửi hai môn thành công, giỏ trống; xóa môn có kế hoạch bị chặn.
- Dự án Trúc hiển thị, tìm tên có dấu được, không có kết quả có thông báo, q[]/sx[] không gây lỗi PHP.
- Các ca HTTP nghiệp vụ dùng CSDL riêng; không có Warning/Notice/Deprecated/Fatal hoặc PDOException trong phản hồi và log của bản thử.
- Chạy lại thí nghiệm Phần C: [ket-qua-phan-c-20261027.txt](ket-qua-phan-c-20261027.txt). Bản này sao chép cấu trúc bằng LIKE, không dùng nó để chứng minh FK; FK được kiểm tra riêng bằng schema và migration.
- ERD đối chiếu đủ tên 22 bảng/22 FK với schema, xuất lại PNG/SVG và kiểm tra hình.

Sau các ca tạm đạt, đã chạy bộ cài trên máy hiện tại: có sao lưu SQL ngoài webroot, 22 bảng/30 môn, giữ dữ liệu và mật khẩu cũ. Không tự nhập JSONL cũ hoặc tạo liên hệ/tài khoản kiểm thử trong CSDL thật. Server/CSDL thử được dọn sau khi hoàn tất.

## Các phần còn thiếu để hoàn thiện theo đề

| Người/phần | Tình trạng và việc còn làm |
| --- | --- |
| Trúc – chức năng 1 và cá nhân | Liên hệ MySQL và danh sách dự án đã chạy. Dự án với tìm/sắp xếp được xem là một nhóm chức năng CSDL, không tự tính là hai; cần thêm một chức năng CSDL cá nhân độc lập. GPA/quiz vẫn dùng PHP/session. Trúc đã có commit cá nhân mới, nên nhận xét cũ “chưa có commit sau btn5” không còn đúng. |
| Uyên – chức năng 2 và 4 | Tìm/lọc và tài khoản qua kiểm thử hồi quy; hai bảng kỹ năng/thời khóa biểu đã được giữ. Cần minh chứng W3C, Mobile A11y và Bảng 3/4/5 ở bản chốt. |
| Hải – chức năng 3 | CRUD và quyền giữ nguyên, quản trị đã đọc MySQL. Lỗi form hiện gom đầu form, đề yêu cầu lỗi cạnh từng ô; cần hoàn thiện cách hiển thị. Avatar/lượt xem là hai chức năng CSDL, cần cập nhật ảnh minh chứng. |
| Duy – chức năng 5 | Transaction, rollback, snapshot và tranh chỗ đều đạt. Bảng tv_thoi_tiet_duy còn thiếu mssv/FK theo quy cách Phần B; món ăn và oẳn tù tì đã có bảng/FK riêng. Chuẩn hóa chú thích đầu tệp 3–5 dòng/cách thử ở các tệp còn thiếu. |
| Hợp – chức năng 6 và tổng hợp | API/form dự phòng đã chạy; cần đo lại debounce một request, cập nhật header không tải trang trên bản chốt, tổng hợp phân công/Bảng 1–5 và bộ nộp. |
| Dữ liệu cũ | JSONL liên hệ được giữ làm bản lưu, không còn runtime. Nếu muốn các liên hệ cũ xuất hiện trong MySQL, cần bước nhập có đối chiếu và chống trùng; bản vá này không tự thực hiện. |
| Chất lượng | Chưa đo lại W3C toàn bộ trang, bố cục 360px và Lighthouse Mobile >=90 cho index/danh-sach/dang-ky. Ảnh cũ không đủ khẳng định bản hiện tại đạt. Không suy ra chất lượng giao diện từ HTTP 200. |
| Phần C | Đã có đủ bốn chủ đề và thí nghiệm thực tế, giữ ghi chú giới hạn. Cần đối chiếu các số dòng source sau bản vá, thay các nhận xét “hiện tại” của snapshot 57a3094 khi đưa vào PDF, thêm ảnh minh chứng theo yêu cầu. Không biến kết quả PDO/HTTP thành bằng chứng đã demo hai trình duyệt. |
| Bộ nộp | Chưa được đối chiếu một báo cáo PDF cuối. Cần Bảng 1–5 đầy đủ, ERD mới, git diff --stat btn5 <commit chốt>, ít nhất 3 nguồn tin cậy, khai báo AI, mã commit và bảng phân công; ZIP đúng tên bỏ .git/vendor/runtime riêng tư. Không kết luận được điểm số trước khi kiểm tra bộ nộp. |

Không tự sửa tất cả các mục còn thiếu thành tính năng mới trong lượt này: phạm vi là khôi phục tính tương thích với mốc gốc, tích hợp phần mới đã thêm và đánh giá phần cần làm tiếp. Chưa commit/push.
