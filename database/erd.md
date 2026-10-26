# ERD EduGPA

[Ảnh PNG](erd.png) · [Bản vector SVG](erd.svg) · [Nguồn Graphviz DOT](erd.dot)

Sơ đồ được dựng từ `schema.sql`: **21 bảng, 21 khóa ngoại**. Bao gồm bảng chính, bảng tài khoản/phiên đăng nhập, giỏ/yêu thích và toàn bộ bảng `tv_` đang được khai báo, gồm kỹ năng và thời khóa biểu của Huỳnh Phương Uyên, check-in thời tiết của Duy. Việc một bảng xuất hiện trong ERD không khẳng định mọi trang đã tích hợp sử dụng bảng đó.

`tv_thoi_tiet_duy` hiện chưa có cột `mssv` hoặc khóa ngoại trong schema, nên được vẽ độc lập. Sơ đồ phản ánh đúng cấu trúc hiện tại; việc bổ sung liên kết thành viên theo đề là thay đổi schema riêng.

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

Liên hệ hiện vẫn đọc/ghi JSONL trong runtime; cần chuyển sang bảng `lien_he` trước khi loại bỏ tệp dữ liệu liên hệ cũ. Đừng xóa dữ liệu đang dùng chỉ vì bảng đã có trong sơ đồ.
