# ERD EduGPA

[Ảnh PNG](erd.png) · [Bản vector SVG](erd.svg) · [Nguồn Graphviz DOT](erd.dot)

Sơ đồ được dựng từ `schema.sql`: **24 bảng, 25 khóa ngoại**. Có đủ bảng tài khoản/phiên, giỏ/yêu thích, liên hệ, dữ liệu cá nhân và bảng dự án `tv_du_an` của Trúc. Việc một bảng xuất hiện trong ERD không khẳng định mọi trang đã tích hợp sử dụng bảng đó.

`tv_thoi_tiet_duy.mssv` là khóa ngoại tới `thanh_vien.mssv`, xóa thành viên thì xóa check-in của thành viên đó (CASCADE). Chỉ mục `(mssv, trang_thai)` phục vụ thống kê riêng từng người. Bộ cài gắn lượt cũ chưa có MSSV với Duy và không thay ID/trạng thái/thời gian.

- `PK`: khóa chính; nhiều cột PK trong cùng bảng tạo khóa ghép.
- `FK`: khóa ngoại; `UQ`: ràng buộc duy nhất. UNIQUE nhiều cột ghi ở cuối thẻ bảng.
- `?` sau kiểu dữ liệu: cột cho phép NULL.
- Nhãn cạnh ghi hành vi `ON DELETE`; khi SQL không ghi, MySQL mặc định RESTRICT.
- Đầu cạnh ghi số lượng liên kết; các bảng trung gian thể hiện quan hệ nhiều–nhiều.
- Chi tiết kế hoạch tham chiếu môn học bằng RESTRICT, còn xóa kế hoạch xóa chi tiết bằng CASCADE. Các cột mã/tên/tín chỉ trong chi tiết là snapshot tại lúc gửi.

SVG giữ nét khi phóng to hoặc đưa vào báo cáo. Nếu sửa schema, cập nhật nguồn DOT và xuất lại hình. Với Graphviz đã cài:

```bash
dot -Tpng database/erd.dot -o database/erd.png
dot -Tsvg database/erd.dot -o database/erd.svg
```

Mốc Bài 5 dùng cho đối chiếu: tag `btn5` tại commit `8ea75efc4a88469208496161e4478c48a1fe79c5`, thời gian tác giả 23:19:55 ngày 16/10/2026 (UTC+7). Dùng `git diff --stat btn5 HEAD` sau khi commit bản Bài 6 cần ghi vào báo cáo. Các thay đổi chưa commit không nằm trong kết quả lệnh này.

Liên hệ hiện đọc/ghi MySQL. Bộ cài bổ sung hai cột điện thoại/ưu tiên cho bảng cũ và giữ dữ liệu SQL đã có. JSONL cũ không còn là nguồn runtime; giữ tệp cũ để đối chiếu hoặc nhập dữ liệu riêng, không xóa tùy tiện.

GPA/quiz của Trúc: `tv_truc_gpa`, `tv_truc_quiz` liên kết MSSV tới `thanh_vien`, chỉ mục `(mssv, pham_vi, id)` tách kết quả theo người xem.
