# 2.3. Phần C – Câu hỏi thảo luận (2,0 điểm)

**Cập nhật trạng thái ngày 27/10/2026:** MonHoc đã có soCho; API thêm hợp lệ trả 200. CSDL mới có 24 bảng/25 FK. Kết quả vá và phép đo mới nằm trong [biên bản bản chốt](../kiemtra/ket-qua-va-ban-chot-20261027.md).

**Phạm vi số đo lịch sử:** các thí nghiệm, số dòng và bảng kết quả gốc dưới đây tham chiếu commit `57a3094`, bổ sung bởi [log chạy lại](../kiemtra/ket-qua-phan-c-20261027.txt). Mọi lỗi chỉ xuất hiện ở mốc cũ được ghi rõ là lịch sử; không coi đó là trạng thái hiện tại. Log gốc được giữ nguyên để truy vết. Khi xuất PDF cần ghi commit của từng phép đo, không thay số liệu cũ bằng số mới mà thiếu căn cứ.

Website EduGPA dùng nghiệp vụ đăng ký môn học. Vì vậy, các thuật ngữ của đề được đối chiếu như sau:

| Trong đề | Trong website nhóm |
| --- | --- |
| Sản phẩm; bảng sản phẩm | Môn học; `mon_hoc` |
| Giá (`gia`) | Tín chỉ (`so_tin_chi`), chỉ tương ứng cho thí nghiệm sắp xếp; website không có giá tiền |
| Tồn kho (`ton_kho`) | Số chỗ (`so_cho`) |
| Đơn hàng; chi tiết đơn | `ke_hoach_hoc_tap`; `chi_tiet_ke_hoach` |
| Đơn giá tại thời điểm mua | Snapshot tín chỉ, mã và tên môn tại thời điểm đăng ký |

**Phạm vi minh chứng:** thử bằng PHP 8.2/PDO, MariaDB `10.4.32`, 30 môn từ CSDL local đã nâng cấp. CSDL thử có tên ngẫu nhiên `edugpa_pc_<12 ký tự hex>`; sao chép cấu trúc bằng `CREATE TABLE ... LIKE`, chỉ sao chép dữ liệu công khai môn/danh mục, tạo tài khoản giả riêng. Không phải thử cài mới từ `schema.sql`; `CREATE TABLE ... LIKE` cũng không sao chép các khóa ngoại. Vì thế kết quả dưới đây chứng minh hành vi SQL, giao dịch và các request được nêu, không chứng minh tính đầy đủ của bộ cài hay ràng buộc khóa ngoại trên bản cài mới.

Lệnh chạy lại từ thư mục dự án:

```powershell
& C:\xampp\php\php.exe kiemtra/thu-nghiem-phan-c.php |
    Set-Content -Encoding utf8 kiemtra/ket-qua-phan-c.txt
```

Mã thí nghiệm: [thu-nghiem-phan-c.php](../kiemtra/thu-nghiem-phan-c.php). Kết quả nguyên văn: [ket-qua-phan-c.txt](../kiemtra/ket-qua-phan-c.txt). Cần MySQL local hoạt động và tài khoản có quyền tạo/xóa CSDL thử. Script tự dọn CSDL trong `finally`. Các biến thể cố tình lỗi chỉ tồn tại trong bộ thử; mã ứng dụng giữ nguyên. Các phần dưới có SQL để chạy tương đương trong phpMyAdmin; lần đo này dùng PDO/HTTP local, chưa chụp màn hình phpMyAdmin hoặc hai trình duyệt.

## 1. Lệnh và dữ liệu

### (a) Ghép từ khóa `Levi's` và sử dụng prepared statement

Trong website hiện tại, `danh-sach.php` gọi `KhoMonHoc::dem()` và `KhoMonHoc::trang()`. Điều kiện tìm kiếm thực sự nằm ở `src/Data/KhoMonHoc.php`, phương thức `dieuKien()`. Biến thể ghép thẳng từ khóa tương ứng được chạy riêng trong bộ thử:

```php
$q = "Levi's";
$sql = "SELECT * FROM mon_hoc
        WHERE (ten_mon LIKE '%$q%' OR ma_mon LIKE '%$q%')
        ORDER BY id DESC LIMIT 6";
$pdo->query($sql);
```

Câu SQL gửi đến MariaDB:

```sql
SELECT * FROM mon_hoc
WHERE (ten_mon LIKE '%Levi's%' OR ma_mon LIKE '%Levi's%')
ORDER BY id DESC LIMIT 6;
```

Kết quả thực tế: **SQLSTATE `42000`, mã lỗi máy chủ `1064`**:

```text
SQLSTATE[42000]: Syntax error or access violation: 1064
You have an error in your SQL syntax; ...
near 's%' OR ma_mon LIKE '%Levi's%') ORDER BY id DESC LIMIT 6' at line 1
```

Dấu `'` trong `Levi's` đóng chuỗi SQL đang mở ở `'%Levi`, khiến phần `s%` bị phân tích như cú pháp lệnh. Đây là lỗi trộn dữ liệu đầu vào vào cấu trúc SQL; người dùng không cần nhập một chuỗi tấn công mới gây lỗi được.

Mã đúng của nhóm dùng hai placeholder cho hai vị trí xuất hiện của từ khóa:

```php
$dk[] = '(ten_mon LIKE ? OR ma_mon LIKE ?)';
$ts[] = '%' . addcslashes($loc['q'], '%_\\') . '%';
$ts[] = '%' . addcslashes($loc['q'], '%_\\') . '%';
// Sau đó prepare($sql), execute($ts), hoặc thêm LIMIT/OFFSET trong trang().
```

Chạy lại qua chính `KhoMonHoc::trang()` với `Levi's`: **không lỗi, trả 0 môn** do dữ liệu hiện có không chứa từ khóa này. `inc/db.php` đặt `PDO::ATTR_EMULATE_PREPARES => false`; tham số được truyền riêng với cú pháp nên dấu nháy là dữ liệu. `addcslashes()` ở đây xử lý ký tự đại diện `%`, `_` và dấu gạch chéo trong tìm kiếm LIKE, không phải cơ chế thay thế prepared statement. Quy tắc placeholder được giải thích trong [tài liệu PDO::prepare](https://www.php.net/manual/en/pdo.prepare.php).

### (b) `ORDER BY ?`, truyền `gia` và xử lý `?sx=bat-ky`

Thí nghiệm giữ đúng yêu cầu truyền chuỗi `gia`, dù website không có cột tên này:

```php
$st = $pdo->prepare('SELECT id, so_tin_chi FROM mon_hoc ORDER BY ? LIMIT 6');
$st->execute(['gia']);
```

Kết quả ID trong lần chạy: **`7, 27, 28, 29, 30, 2`**. Truyền `'so_tin_chi'` cũng cho cùng kết quả. Placeholder biểu diễn một **giá trị chuỗi hằng**, tương đương về ý nghĩa sắp xếp với `ORDER BY 'gia'`, chứ không trở thành tên cột. Mọi dòng có cùng khóa sắp xếp; thứ tự giữa chúng không được câu lệnh bảo đảm. Trong lần đo này thứ tự tình cờ giống tín chỉ tăng vì đường đọc dữ liệu đã chọn; không thể từ đó kết luận `ORDER BY ?` đã sắp đúng. Không có lỗi “Unknown column gia” vì `gia` đang được bind như dữ liệu.

Muốn sắp theo cột phải đưa một biểu thức SQL được chọn từ danh sách cố định vào câu lệnh, như website đang làm:

```php
// danh-sach.php:42–46
$danhSachSapXep = ['moi', 'ten', 'tin-chi-tang', 'tin-chi-giam'];
$sapXep = layChuoiDauVao($_GET['sx'] ?? 'moi');
if (!in_array($sapXep, $danhSachSapXep, true)) {
    $sapXep = 'moi';
}
```

Lớp dữ liệu bảo vệ thêm tại `src/Data/KhoMonHoc.php:142`:

```php
$orderBy = match ($sx) {
    'ten' => 'ten_mon ASC, id',
    'tin-chi-tang' => 'so_tin_chi ASC, id',
    'tin-chi-giam' => 'so_tin_chi DESC, id',
    default => 'id DESC',
};
```

Như vậy `?sx=bat-ky` trở về `moi` ở trang và vẫn về `id DESC` nếu được gọi trực tiếp ở lớp dữ liệu. Thử so sánh danh sách ID của `bat-ky` với `moi` cho kết quả **`true`**. Không có giá trị `sx` nguyên gốc nào được nối thẳng vào SQL.

### (c) Vì sao vẫn kiểm tra dữ liệu trước khi truy vấn?

Prepared statement bảo vệ ranh giới giữa dữ liệu và cú pháp SQL; nó không tự xác nhận dữ liệu đúng kiểu, đúng miền nghiệp vụ hoặc đúng quyền. Một số nguyên âm được bind an toàn vẫn có thể là số lượng sai; một ID hợp lệ vẫn có thể thuộc người khác. Ngoài ra, tên cột và chiều sắp xếp cần danh sách cho phép vì chúng thuộc cấu trúc lệnh.

Ví dụ ngay trên website: `danh-sach.php:28` kiểm tra `dm` là số nguyên dương, `tc_toi_da` nằm trong `1..20`, `trang` ít nhất bằng 1. `gio-hang.php` kiểm tra ID và số lượng trước khi gọi service; `src/Services/KeHoachHocTap.php:65` tiếp tục chỉ nhận số lượng từ 1 đến 5. API giỏ dùng `soNguyenApi()` và trả **422** cho kiểu/miền đầu vào sai theo nhánh mã. Nếu chỉ bind `sl=-3`, câu lệnh vẫn có thể đúng cú pháp nhưng phép trừ chỗ lại thành tăng chỗ. Bởi vậy nhóm cần đồng thời: kiểm tra đầu vào, prepared statement, kiểm tra quyền và điều kiện dữ liệu ngay khi cập nhật. Giá trị 422 ở ví dụ này là hành vi từ mã nguồn; bộ thử lần này tập trung vào SQL, quyền và request hợp lệ gây lỗi model, không đo lại toàn bộ ma trận đầu vào.

## 2. Giao dịch và đồng thời

### (a) Bỏ giao dịch và làm lại ca lỗi giữa chừng

Đề được gửi không kèm nội dung **Bảng 3, ca 10**. Bộ thử dùng ca lỗi giữa chừng mà mã nhóm đã mô tả: đăng ký hai môn, môn thứ nhất còn chỗ, môn thứ hai hết chỗ. Nếu ca 10 trong bản đề đầy đủ có bước gây lỗi khác, cần đối chiếu lại tên ca; không khẳng định đã đọc một bảng chưa được cung cấp.

Chuẩn bị trong CSDL thử: không có kế hoạch/chi tiết, môn `id=1` có 2 chỗ, môn `id=2` có 0 chỗ; gọi `taoKeHoach($uid, [1=>1, 2=>1])`. Hàm đọc môn theo `ORDER BY id` nên môn 1 được xử lý trước môn 2.

```sql
UPDATE mon_hoc SET so_cho=2 WHERE id=1;
UPDATE mon_hoc SET so_cho=0 WHERE id=2;
-- Gọi taoKeHoach(uid, [1=>1, 2=>1]) bằng PHP.
SELECT COUNT(*) FROM ke_hoach_hoc_tap;
SELECT COUNT(*) FROM chi_tiet_ke_hoach;
SELECT id, so_cho FROM mon_hoc WHERE id IN (1,2) ORDER BY id;
```

Biến thể không giao dịch dùng một kết nối PDO thực nghiệm vô hiệu hóa `beginTransaction`, `commit`, `rollBack`; `inTransaction()` trả false. Hàm nghiệp vụ của commit hiện tại vẫn được gọi nguyên vẹn. Đây là cách tái hiện autocommit mà không để bản lỗi trong ứng dụng.

| Chế độ | Số kế hoạch sau lỗi | Số chi tiết sau lỗi | Chỗ môn 1 | Chỗ môn 2 |
| --- | ---: | ---: | ---: | ---: |
| Trước khi gọi | 0 | 0 | 2 | 0 |
| Không transaction | 1 | 1 | 1 | 0 |
| Có transaction, sau khi đặt lại dữ liệu ban đầu | 0 | 0 | 2 | 0 |

Cả hai lượt đều báo **“Môn MAT102 không còn đủ chỗ đăng ký.”** Không transaction: INSERT cha, UPDATE môn 1 và INSERT chi tiết 1 đã tự commit; lỗi môn 2 không thu hồi các thay đổi trước đó. Có transaction: `src/Data/KhoKeHoach.php:52` mở giao dịch, dòng 105 chỉ commit khi hoàn tất, dòng 108 rollback khi có ngoại lệ. Kết quả không còn kế hoạch dở dang và không mất chỗ môn 1. Lượt lỗi xảy ra trước bước xóa giỏ trong hàm, nên không chạy câu DELETE giỏ; bộ thử không dùng dữ liệu giỏ để chứng minh riêng ca rollback này.

### (b) Hai khách cùng đăng ký chỗ cuối

Đặt `so_cho=1` cho môn 1; khởi động hai tiến trình PHP, mỗi tiến trình có kết nối PDO và tài khoản thử riêng, cùng gọi `taoKeHoach()` cho môn đó. Kết quả thực tế: **một `THANH_CONG`, một `THIEU_CHO`; chỉ có một kế hoạch và số chỗ cuối bằng 0**. Hai tiến trình được khởi động liền nhau nhưng không có hàng rào bảo đảm chúng chạm UPDATE đúng cùng một thời điểm; kết quả xác nhận an toàn trong lượt cạnh tranh đã chạy.

Câu quyết định tại `src/Data/KhoKeHoach.php:71`:

```sql
UPDATE mon_hoc
SET so_cho = so_cho - ?
WHERE id = ? AND so_cho >= ?;
```

```php
$stUpdate->execute([$sl, $monId, $sl]);
if ($stUpdate->rowCount() !== 1) {
    throw new MonKhongDuChoException(...);
}
```

Kiểm tra và trừ chỗ nằm trong cùng một UPDATE. Với InnoDB, người ghi cạnh tranh phải chờ khóa; sau khi người trước hoàn tất, UPDATE còn lại phải thỏa điều kiện trên giá trị hiện hành. Khi chỗ bằng 0, nó không cập nhật dòng nào và hàm rollback kế hoạch vừa tạo. Nếu chỉ `SELECT so_cho` trước rồi UPDATE không điều kiện, hai khách có thể cùng đọc 1; đến lúc UPDATE, kết quả đọc đã cũ, dẫn đến nhận quá số chỗ, tồn âm hoặc ghi đè tùy cách viết UPDATE. SELECT kiểm tra trước chỉ có ích cho thông báo, không đủ bảo vệ cạnh tranh. Có thể dùng `SELECT ... FOR UPDATE` đúng trong giao dịch, nhưng mã hiện tại chọn UPDATE có điều kiện.

**Giới hạn đối với thao tác hai trình duyệt của đề:** thử nghiệm hai tiến trình ở trên chứng minh cạnh tranh ở tầng dữ liệu; chưa phải minh chứng hai trình duyệt. Tại snapshot `57a3094`, HTTP thêm giỏ từng trả 500 vì thiếu `MonHoc::$soCho` (xem log gốc). Mã đã khôi phục thuộc tính và các lượt kiểm tra sau trả 200; lỗi này không còn là trở ngại hiện tại. Vẫn cần phân biệt kiểm thử dữ liệu, HTTP và demo hai trình duyệt.

Để bổ sung minh chứng giao diện: dùng hai tài khoản trên cửa sổ thường/ẩn danh, đặt môn thử còn 1 chỗ, mỗi giỏ thêm 1, gửi lần lượt; người đầu phải thành công, người sau phải bị từ chối, sau đó kiểm tra ba bảng bằng SQL ở (a). Chỉ thử với dữ liệu tạm và khôi phục số chỗ ban đầu. Việc thêm giỏ không đồng nghĩa giữ chỗ.

### (c) Lưu đơn giá/snapshot có trái chuẩn hóa không?

Trong bán hàng, “giá hiện tại của sản phẩm” và “đơn giá đã thỏa thuận cho một dòng đơn hàng” là hai dữ kiện khác nhau. Giá hiện tại phụ thuộc sản phẩm; đơn giá giao dịch phụ thuộc dòng đơn hàng. Nếu coi đơn giá là thuộc tính của giao dịch thì lưu nó tại chi tiết đơn là mô hình đúng, không thể kết luận cứ lặp con số là vi phạm chuẩn hóa. Nếu cố tình sao chép cùng một thuộc tính hiện tại để tăng tốc thì đó mới là phi chuẩn hóa cần kiểm soát cập nhật.

EduGPA áp dụng ý tưởng tương tự bằng `ma_mon`, `ten_mon`, `so_tin_chi` trong `chi_tiet_ke_hoach`: đó là thông tin đã ghi nhận lúc đăng ký. Hàm `taoKeHoach()` lấy snapshot từ CSDL, không nhận tín chỉ/tên do trình duyệt gửi. Nếu JOIN và luôn dùng tín chỉ hiện tại, một lần sửa môn sẽ làm tổng tín chỉ của kế hoạch lịch sử thay đổi. Bộ thử lưu kế hoạch, đổi tên và tín chỉ môn 1 thành 19 rồi đọc lại chi tiết; **snapshot giữ nguyên (`true`)**. Đổi tên/tín chỉ trong thử nghiệm chỉ diễn ra ở CSDL tạm.

## 3. Truy vấn nhanh và đủ ít

### (a) EXPLAIN trước và sau chỉ mục ghép

Do website không có `gia`, truy vấn tương ứng bộ lọc `?dm=1&sx=tin-chi-tang` là:

```sql
EXPLAIN SELECT * FROM mon_hoc
WHERE danh_muc_id=1
ORDER BY so_tin_chi ASC, id LIMIT 6 OFFSET 0;

EXPLAIN SELECT * FROM mon_hoc
WHERE (ten_mon LIKE '%Web%' OR ma_mon LIKE '%Web%')
ORDER BY id DESC LIMIT 6 OFFSET 0;

CREATE INDEX idx_pc_dm_tc ON mon_hoc(danh_muc_id, so_tin_chi);
-- Chạy lại hai câu EXPLAIN trên.
DROP INDEX idx_pc_dm_tc ON mon_hoc; -- dọn chỉ mục thử
```

Kết quả thực đo trên CSDL thử 30 môn:

| Truy vấn | Thời điểm | type | key | rows | Extra |
| --- | --- | --- | --- | ---: | --- |
| Danh mục 1, tín chỉ tăng | Trước | ref | danh_muc_id | 7 | Using where; Using filesort |
| Danh mục 1, tín chỉ tăng | Sau | ref | idx_pc_dm_tc | 7 | Using where |
| Từ khóa Web, ID giảm | Trước | index | PRIMARY | 6 | Using where |
| Từ khóa Web, ID giảm | Sau | index | PRIMARY | 6 | Using where |

Chỉ mục ghép bắt đầu bằng cột lọc bằng `danh_muc_id`, tiếp theo là `so_tin_chi`, nên giúp vừa tìm nhóm môn vừa đọc theo tín chỉ. `Using filesort` biến mất; `rows` vẫn là 7, không nên viết rằng số dòng ước lượng luôn giảm sau khi thêm index. InnoDB chứa khóa chính trong chỉ mục phụ nên với cấu trúc này vẫn phục vụ được tiêu chí phụ `id`. `Using filesort` chỉ tên một thao tác sắp xếp thêm, không có nghĩa chắc chắn ghi ra đĩa.

Với `LIKE '%Web%'`, không biết tiền tố bắt đầu nên B-tree trên tên không định vị được một khoảng khóa theo từ khóa. Ngược lại, `LIKE 'Web%'` có thể dùng khoảng tiền tố nếu điều kiện và bộ tối ưu phù hợp. Trong kết quả ở đây, `key=PRIMARY` không có nghĩa chỉ mục tìm được chuỗi Web: hệ quản trị đọc theo ID để phục vụ ORDER BY/LIMIT rồi lọc `Using where`. `rows=6` là ước lượng kế hoạch, không chứng minh đúng 6 dòng được đọc thực tế. Chỉ mục ghép danh mục/tín chỉ không giúp điều kiện tìm kiếm này. Cơ chế khoảng tiền tố được mô tả trong [tài liệu MySQL về range optimization](https://dev.mysql.com/doc/refman/8.4/en/range-optimization.html).

### (b) Đếm SQL, kiểm tra N + 1 và đối chiếu cách sửa

Đo GET HTML bằng một tiến trình riêng cho mỗi request. Trước/sau include trang, lấy chênh lệch `SHOW SESSION STATUS LIKE 'Com_select'` trên cùng kết nối. Lượt GET này chỉ có các câu SELECT ứng dụng; SHOW đo đếm không tính vào `Com_select`. Fixture: cookie `da_xem=1,2,3,4`, giỏ 3 môn; phiên đăng nhập đã có `chu_gio_hang`, không cookie ghi nhớ. Không cộng các request fetch do JavaScript phát sinh sau khi nhận HTML.

| Request hiện tại | Chưa đăng nhập | Đã đăng nhập |
| --- | ---: | ---: |
| GET `index.php` | 3 SELECT | 6 SELECT |
| GET `gio-hang.php` | 2 SELECT | 7 SELECT |

Giải thích số đếm: trang chủ khách có 1 truy vấn môn mới, 1 truy vấn đã xem và 1 truy vấn môn trong giỏ của header. Đăng nhập cộng 2 lượt tải giỏ tài khoản trong constructor/`layDanhSachChiTiet()` và 1 lượt đọc tài khoản. Trang giỏ khách lấy chi tiết giỏ 1 lần ở thân trang và 1 lần ở header. Khi đăng nhập, hai đối tượng service gây 4 lượt đọc giỏ, 2 lượt đọc nhóm môn và 1 lượt đọc tài khoản, tổng 7. Các con số có thể thay đổi nếu giỏ rỗng, mới gộp giỏ khách, hoặc khôi phục đăng nhập bằng cookie.

**Kết luận từ commit mới nhất:** hai khối được hỏi đã sửa cách truy vấn từng ID. `index.php` gom ID rồi gọi `theoNhieuId()`; `KeHoachHocTap::layDanhSachChiTiet()` gọi cùng hàm một lần trước vòng lặp. `src/Data/KhoMonHoc.php:62` thực hiện:

```php
$marks = implode(',', array_fill(0, count($ids), '?'));
$stmt = $this->db->prepare('SELECT * FROM mon_hoc WHERE id IN (' . $marks . ')');
$stmt->execute(array_values($ids));
```

Các vòng lặp sau đó chỉ ghép dữ liệu trong PHP, không query từng môn. Chuỗi placeholder được sinh theo số ID; các ID vẫn được bind, không ghép giá trị thẳng vào SQL.

Để có số “trước/sau” mà không bịa lịch sử commit, bộ thử chạy riêng cách cũ giả lập `foreach ($ids as $id) $kho->timTheoId($id)` và cách IN hiện tại trên cùng dữ liệu:

| Khối được đo riêng | Vòng lặp truy vấn thực nghiệm | Cách IN hiện tại |
| --- | ---: | ---: |
| Đã xem, 4 ID | 4 SELECT | 1 SELECT |
| Chi tiết giỏ, 3 ID | 3 SELECT | 1 SELECT |

Đó là N lượt đọc môn thay bằng 1; khi cộng một truy vấn lấy danh sách cha thì mẫu tổng quát trở thành N + 1. Không khẳng định commit trước của nhóm có đúng các số này. Bản hiện tại vẫn có truy vấn giỏ lặp giữa thân trang/header, nhưng số lượt không tăng tuyến tính theo số môn nên đây là truy vấn trùng, không phải N + 1. Không cần thay mã đang dùng IN chỉ để tạo một bản “sửa” giả.

### (c) Một triệu dòng và `?trang=50000`

Trang nhóm có 6 môn mỗi trang, nên trang 50.000 tạo **`OFFSET = (50000 - 1) × 6 = 299994`**. Khi đủ một triệu dòng khớp bộ lọc, câu lệnh phải đi qua khoảng 299.994 vị trí trước khi trả tối đa 6 dòng; nếu còn lọc/sắp xếp không được index hỗ trợ thì số dòng xét có thể lớn hơn. LIMIT nhỏ không làm OFFSET lớn trở nên rẻ. Ngoài ra, mỗi lần tải danh sách còn có `SELECT COUNT(*)` để tính tổng trang.

Với dữ liệu hiện có 30 môn, `danh-sach.php` chặn trang vượt quá bằng `min($trangHienTai, $soTrang)`, nên nhập 50.000 chỉ tới trang cuối, không tạo OFFSET 299.994 trên bộ dữ liệu này. Khi mở rộng, có thể dùng phân trang theo khóa: với thứ tự ID giảm, `WHERE id < ? ORDER BY id DESC LIMIT 6`; với tín chỉ tăng, dùng cặp con trỏ `(so_tin_chi, id)` và chỉ mục tương ứng. Cách này thuận lợi cho “trang tiếp”, nhưng cần thiết kế thêm nếu muốn nhảy thẳng tới một số trang bất kỳ. Đây là phân tích thuật toán; chưa nạp/đo hiệu năng một triệu dòng và không có số mili giây thực nghiệm.

## 4. Mật khẩu, quyền và dữ liệu lộ ra ngoài

### (a) Hai tài khoản cùng mật khẩu, hai chuỗi băm khác nhau

Bộ thử tạo `pc_a`, `pc_b` trong CSDL tạm qua `KhoNguoiDung::dangKy()`, cùng mật khẩu thử `PhanC_2026!`; mỗi lần gọi `password_hash($password, PASSWORD_DEFAULT)` riêng. Đọc lại bằng:

```sql
SELECT ten_dang_nhap, mat_khau FROM nguoi_dung
WHERE ten_dang_nhap IN ('pc_a','pc_b') ORDER BY id;
```

Kết quả nguyên văn hai hash có trong tệp kết quả kèm báo cáo; chúng khác nhau, cùng có định dạng bcrypt `$2y$10$...` trên PHP thử nghiệm. Gọi `password_verify('PhanC_2026!', $hash)` với từng hash trả **`true, true`**. Mật khẩu/tài khoản nêu ở đây chỉ là fixture đã xóa, không phải thông tin tài khoản thật.

`password_hash()` tự sinh salt ngẫu nhiên; hash lưu thông tin thuật toán, cost và salt. `password_verify()` đọc các thông tin đó để kiểm tra mật khẩu, không tạo một hash mới với salt ngẫu nhiên rồi so hai chuỗi. Hai hash khác nhau vì thế vẫn xác minh cùng mật khẩu. Cách tạo hash của trang nhóm nằm tại `dang-ky.php:61`; kiểm tra đăng nhập ở `dang-nhap.php:32`, kèm nhánh rehash khi cần nâng cấp cấu hình. Xem [tài liệu password_hash](https://www.php.net/manual/en/function.password-hash.php).

Không dùng MD5/SHA-1 trực tiếp để lưu mật khẩu: chúng tính rất nhanh, không có cơ chế cost thích hợp để làm chậm việc đoán mật khẩu hàng loạt; băm không salt còn làm các mật khẩu giống nhau có cùng hash và thuận lợi cho bảng tra sẵn. Thêm salt vào MD5/SHA-1 không khắc phục được việc chúng quá nhanh. Nên dùng API băm mật khẩu chuyên dụng, chọn cost phù hợp máy chủ; lưu ý `PASSWORD_DEFAULT` có thể thay đổi thuật toán theo phiên bản PHP. Lý do chính ở đây là chống đoán mật khẩu, không chỉ là vấn đề va chạm của hàm băm.

### (b) Bảng trang/API cần quyền và thử đổi ID

Các số dòng sau tham chiếu mã commit `57a3094`. “Công khai” nghĩa là không buộc đăng nhập để sử dụng chức năng đó; các thao tác ghi vẫn có thể yêu cầu CSRF hoặc token riêng.

| Trang/API/chức năng | Quyền cần có | Vị trí kiểm tra máy chủ |
| --- | --- | --- |
| `dat-hang.php` – gửi kế hoạch | Đăng nhập | Dòng 21: `$nd = yeuCauDangNhap()`; UID tạo kế hoạch lấy từ `$nd['id']`, không lấy từ POST |
| `don-hang.php` – danh sách/chi tiết | Đăng nhập; khách chỉ xem của mình, admin xem toàn bộ | Dòng 30–32 lấy user/vai trò; dòng 85 gọi `chiTiet`; `KhoKeHoach.php:127` thêm `AND nguoi_dung_id = ?`; danh sách khách dùng `dsCuaToi()` |
| `don-hang.php` – POST đổi trạng thái | `quan_tri` | Dòng 39–41 chặn người thường bằng `yeuCauQuyen('quan_tri')`; kiểm tra CSRF trước ghi |
| `quan-tri.php` | `quan_tri` | Dòng 11: `yeuCauQuyen('quan_tri')` |
| `quan-tri-mon-hoc.php` – xem/thêm/sửa | `quan_tri` | Dòng 16: `yeuCauQuyen('quan_tri')`; POST còn kiểm tra CSRF |
| `xoa-mon-hoc.php` – xóa | `quan_tri` | Dòng 15: `yeuCauQuyen('quan_tri')`; phải POST, có CSRF; GET bị 405 trước bước kiểm quyền |
| `api/yeu-thich.php` – GET/POST | Đăng nhập | Dòng 20–22: `nguoiDung()`, null thì JSON 401, UID lấy từ user đã xác thực |
| Trang Hải `thanhvien/3120224042_hai/gioithieu.php` – POST avatar | Chủ trang theo bảng `thanh_vien` | Dòng 13–15 tính `$laChuTrang`, dòng 21 chặn; `KhoAnhDaiDien.php:17` truy vấn cả MSSV và UID; admin không tự được đặc cách |
| Trang Hợp `thanhvien/3120224064_hop/gioithieu.php` – POST bình chọn | Tài khoản đăng nhập tồn tại | `inc/binh-chon-hop.php:20` đọc UID session, dòng 23 kiểm tra tài khoản; dòng 39–40 từ chối khách; không nhận UID từ form |
| `api/san-pham.php` – GET danh sách/chi tiết | Công khai | Không có yêu cầu đăng nhập; chỉ trả dữ liệu môn công khai, không có API sửa/xóa môn tại đây |
| `api/gio-hang.php`; `gio-hang.php`; `xu-ly-gio-hang.php` | Cho phép khách có session | Giỏ khách theo session; giỏ tài khoản theo UID session; ghi cần CSRF. `xu-ly-gio-hang.php` chuyển tới luồng giỏ chung |
| Các trang chủ/danh sách/chi tiết/giới thiệu/liên hệ và GET trang thành viên | Công khai | Không áp đặt vai trò quản trị chỉ vì có ID trên URL; POST avatar và bình chọn là hai ngoại lệ ở trên |
| Đăng ký, đăng nhập, quên/đặt lại mật khẩu | Công khai hoặc token đặt lại hợp lệ | Đăng ký gán cố định vai trò khách trong `KhoNguoiDung::dangKy()`; đặt lại xác minh token, không cho chọn tài khoản đích bằng ID tùy ý |
| `dang-xuat.php` | Phiên hiện tại, POST + CSRF | Dòng 12–19 kiểm tra phương thức/token; không có `yeuCauDangNhap()`, không nhận UID tài khoản đích |

Hàm bảo vệ chung ở `inc/xac-thuc.php:151` chuyển người chưa đăng nhập về `dang-nhap.php`; `yeuCauQuyen()` ở dòng 163 trả 403 nếu vai trò không khớp. Ẩn nút admin trong header chỉ là giao diện, các kiểm tra trên máy chủ mới quyết định quyền.

**Các thử nghiệm đã chạy**, trên server HTTP local tạm với hai tài khoản giả; có token hợp lệ để tránh nhầm lỗi CSRF với lỗi quyền:

| Cách thay đổi dữ liệu đầu vào | Kết quả thực tế |
| --- | --- |
| A GET `don-hang.php?id=<kế hoạch của B>` | HTTP **404**; tầng dữ liệu trả null vì không khớp cả ID lẫn chủ sở hữu |
| A GET `quan-tri.php` và `quan-tri-mon-hoc.php?sua=1` | HTTP **403** |
| A POST ô ẩn `id=<kế hoạch của B>`, `trang_thai=da_duyet`, CSRF đúng tới `don-hang.php` | HTTP **403**, chặn trước câu UPDATE trạng thái |
| A POST ô ẩn `id=1`, CSRF đúng tới `xoa-mon-hoc.php` | HTTP **403**, chặn trước xóa môn |
| A POST JSON `{"id":1,"chon":true,"uid":B,"nguoi_dung_id":B}` tới API yêu thích | HTTP **200**; yêu thích A thành `[1]`, B vẫn `[]`; hai trường giả UID bị bỏ qua |
| Khách GET `api/yeu-thich.php` | HTTP **401**, `{"loi":"Vui lòng đăng nhập."}` |
| Gọi kho dữ liệu xóa môn chỉ có trong giỏ B nhưng truyền UID A | Trả false, giỏ B còn nguyên `{"2":1}` |

SQL cô lập giỏ nằm ở `KhoTrangThaiNguoiDung.php:63` và `:69`, đều chứa `WHERE nguoi_dung_id = ? AND mon_hoc_id = ?`. Trường ID trong API giỏ/yêu thích là **ID môn**, không phải ID người dùng. Đổi ID môn sang một môn công khai khác trong giỏ của chính mình không phải vượt quyền. Prepared statement không thay thế điều kiện chủ sở hữu này.

Các kiểm tra avatar/bình chọn/đặt lại mật khẩu trong bảng là đối chiếu mã nguồn; chưa thử HTTP lại toàn bộ chúng trong lượt này. Bộ thử cũng chưa chạy đầy đủ kịch bản admin sửa/xóa thành công hoặc tất cả hình dạng ID độc hại. Không suy rộng vài request trên thành tuyên bố “mọi API đều an toàn”.

### (c) Trường API trả về và lý do session chỉ giữ UID về danh tính

Website có ba endpoint trong thư mục `api`:

| API | Các trường phản hồi thành công |
| --- | --- |
| GET `api/san-pham.php?id=...` | `du_lieu`: một môn |
| GET `api/san-pham.php` tìm kiếm | `du_lieu`: mảng môn; `tong`, `trang`, `so_trang`, `da_them` |
| GET `api/gio-hang.php` | `so_mon`, `du_lieu`, `csrf` |
| POST `api/gio-hang.php` | Theo nhánh thành công của mã: `so_mon`, `du_lieu`, `thong_bao`; request hợp lệ tại snapshot `57a3094` từng trả 500 do thiếu `soCho`; các lượt sau sửa đã trả 200, xem biên bản bản chốt |
| GET/POST `api/yeu-thich.php` | `du_lieu`: mảng ID môn yêu thích của tài khoản hiện tại |
| Phản hồi lỗi | `loi`; lỗi không dự kiến được chuẩn hóa 500 thành `{"loi":"Lỗi máy chủ"}` |

Mỗi môn công khai chỉ gồm **`id`, `maMon`, `tenMon`, `soTinChi`, `anh`, `trangThai`**, do `src/Models/MonHoc.php:12` triển khai `JsonSerializable`. API không trả nguyên dòng `SELECT *`, không có `so_cho` trong JSON môn hiện tại. Mỗi dòng giỏ gồm **`id`, `maMon`, `tenMon`, `soTinChi`, `soLuong`, `tongTinChi`** theo `KeHoachHocTap::layDanhSachChiTiet()`. Trường `so_mon` đang lấy tổng số lượng, không phải nhất thiết là số dòng giỏ phân biệt.

Không `json_encode()` nguyên dòng bảng `nguoi_dung` vì có `mat_khau` và thông tin cá nhân/quyền; hash mật khẩu bị lộ vẫn cho phép kẻ khác đoán mật khẩu ngoại tuyến. Khi bổ sung cột nhạy cảm trong tương lai, trả nguyên dòng còn có nguy cơ tự làm lộ cột mới. Nếu cần API hồ sơ phải chọn từng trường cần thiết, áp dụng quyền truy cập và không trả hash/token. Ngay phương thức đọc user hiện tại cũng dùng SELECT danh sách cột rõ ràng, không lấy `mat_khau` cho header.

“Session chỉ lưu UID” cần hiểu chính xác là **phần danh tính đăng nhập** chỉ giữ UID, không sao chép vai trò làm nguồn quyết định quyền. Session của website vẫn có giỏ, CSRF, thông báo và `chu_gio_hang`. `inc/xac-thuc.php:133` lấy UID; dòng 145 đọc thông tin/vai trò từ CSDL, cache trong cùng request. Nhờ vậy khi quyền bị hạ hoặc tài khoản bị xóa, request tiếp theo dùng trạng thái mới, không tiếp tục tin vai trò cũ trong session. Session là dữ liệu máy chủ chứ không phải biến URL người dùng tùy ý sửa được; lợi ích chính ở đây là tránh quyền bị lưu cũ và duy trì một nguồn dữ liệu quyền thống nhất.

## Ghi chú cần hoàn thiện trước khi nộp minh chứng

Phần lý giải và các bảng số đo phía trên có lệnh, SQL và log đi kèm theo mốc đã ghi. Không dùng số liệu tầng dữ liệu để tuyên bố đã hoàn tất demo hai trình duyệt; cần bổ sung ảnh và các bảng kết quả theo đề khi xuất PDF. Lỗi thiếu soCho thuộc snapshot cũ, đã được sửa; các kết luận hiện hành phải theo biên bản bản chốt. Giữ nguyên log thí nghiệm lịch sử để truy vết.
