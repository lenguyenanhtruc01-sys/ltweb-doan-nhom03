#!/usr/bin/env bash
# tests/bao-mat.sh — Bảng 3 (Bài tập nhóm 7): kiểm thử phủ định, chạy lại sau mỗi lần sửa mã.
# Người phụ trách: Lê Dương Hoàng Hải (3120224042). Chạy trong Git Bash: bash tests/bao-mat.sh
# Không ghi giá trị mã phiên / mã CSRF vào tệp: điền vào biến môi trường khi chạy, ví dụ
#   SID=<PHPSESSID của tài khoản khách> CSRF=<thẻ meta csrf-token> bash tests/bao-mat.sh
# GOC: php -S localhost:8000 router.php. APACHE: http://localhost/ltweb-doan-nhom03 (ca 10).
# Dùng CSDL kiểm thử mới để bộ đếm đăng nhập sai ở ca 12 bắt đầu từ 0.
GOC="${GOC:-http://localhost:8000}"
APACHE="${APACHE:-http://localhost/ltweb-doan-nhom03}"
SID="${SID:-}"
CSRF="${CSRF:-}"
ma() { curl -s -g -o /dev/null -w '%{http_code}' "$@"; }
tieuDe() { echo; echo "=== $1"; }

tieuDe "Ca 1 – Header và cookie (mong đợi: CSP, nosniff, Referrer-Policy; không X-Powered-By; HttpOnly; SameSite=Lax)"
curl -s -I "$GOC/index.php" | grep -iE '^(HTTP|content-security|x-content|referrer|x-powered|set-cookie)' | sed -E 's/(PHPSESSID=)[^;[:space:]]+/\1[ẩn]/'

tieuDe "Ca 2 – POST thiếu mã CSRF (mong đợi: 403, log csrf_sai)"
echo "thiếu _csrf: $(ma -d 'email=hop&mat_khau=matkhau123' "$GOC/dang-nhap.php")"
echo "_csrf=abc:   $(ma -d 'email=hop&mat_khau=matkhau123&_csrf=abc' "$GOC/dang-nhap.php")"
echo "_csrf[]=x:   $(ma -d 'email=hop&mat_khau=matkhau123&_csrf[]=x' "$GOC/dang-nhap.php")"

tieuDe "Ca 3 – API giỏ hàng không có / có X-CSRF-Token (mong đợi: 403 JSON / 200)"
curl -s -b "PHPSESSID=$SID" -H 'Content-Type: application/json' -d '{"id":1,"sl":1}' \
     -w '  -> %{http_code}\n' "$GOC/api/gio-hang.php"
[ -n "$CSRF" ] && curl -s -b "PHPSESSID=$SID" -H 'Content-Type: application/json' \
     -H "X-CSRF-Token: $CSRF" -d '{"id":1,"sl":1}' -w '  -> %{http_code}\n' "$GOC/api/gio-hang.php"

tieuDe "Ca 4 – GET đổi dữ liệu (mong đợi 405 + Allow: POST); khách có mã đúng xóa môn (mong đợi 403, log cam_truy_cap)"
curl -s -o /dev/null -D - "$GOC/dang-xuat.php" | grep -iE '^(HTTP|allow)'
curl -s -o /dev/null -D - "$GOC/xoa-mon-hoc.php?id=0" | grep -iE '^(HTTP|allow)'
[ -n "$SID" ] && [ -n "$CSRF" ] && echo "khách POST xóa: $(ma -b "PHPSESSID=$SID" -d "id=0&_csrf=$CSRF" "$GOC/xoa-mon-hoc.php")"

tieuDe "Ca 5 – Cố định phiên (mong đợi: Set-Cookie trả mã phiên mới)"
cookieMoi=$(curl -s -I -b 'PHPSESSID=tudat1234567890' "$GOC/index.php" | grep -i '^set-cookie: PHPSESSID=[^;]')
if [ -n "$cookieMoi" ] && [[ "$cookieMoi" != *'PHPSESSID=tudat1234567890;'* ]]; then
  echo "$cookieMoi" | sed -E 's/(PHPSESSID=)[^;[:space:]]+/\1[ẩn]/'
else
  echo "Không nhận được mã phiên mới."
fi

tieuDe "Ca 6 – Hết hạn phiên (kết quả của bộ thử CSDL tạm ở cuối)"

tieuDe "Ca 7 – XSS (mong đợi: &lt;b&gt;ao&lt;/b&gt; trong mã nguồn)"
curl -s "$GOC/danh-sach.php?q=%3Cb%3Eao%3C%2Fb%3E" | grep -o '&lt;b&gt;ao&lt;/b&gt;' | head -1

tieuDe "Ca 8 – Tham số dạng mảng (mong đợi: 200/404, không 500)"
for p in 'danh-sach.php?q[]=ao' 'chi-tiet.php?id[]=1' 'danh-sach.php?sx[]=x'; do echo "$p -> $(ma "$GOC/$p")"; done
[ -n "$CSRF" ] && echo "dang-nhap email[]: $(ma -b "PHPSESSID=$SID" -d "email[]=a&mat_khau[]=b&_csrf=$CSRF" "$GOC/dang-nhap.php")"
[ -n "$CSRF" ] && echo "lien-he hoten[]:   $(ma -b "PHPSESSID=$SID" -d "hoten[]=a&email[]=b&_csrf=$CSRF" "$GOC/lien-he.php")"

tieuDe "Ca 9 – .env (hai lệnh git không in gì, kết quả HTTP 500 ở cuối)"
git ls-files .env; git log --all --oneline -- .env; echo "(hết)"

tieuDe "Ca 10 – .htaccess trên Apache (mong đợi: 404 cho .env/.git/inc/vendor; 403 cho .sql/.log/composer.json/uploads; CSS 200)"
for p in .env .git/config inc/db.php vendor/autoload.php database/schema.sql logs/bao-mat.log composer.json uploads/ css/style.css; do
  echo "$p -> $(ma "$APACHE/$p")"
done

tieuDe "Ca 11 – Quyền tối thiểu (mong đợi: CRUD thành công, CREATE và DROP trả 1142)"
echo "Xem kết quả của bộ thử CSDL tạm ở cuối."

tieuDe "Ca 12 – Giới hạn đăng nhập (mong đợi: 5 lần 200, lần 6 trả 429, tài khoản khác trả 303)"
jarKiemThu=$(mktemp) || exit 1
trap 'rm -f "$jarKiemThu"' EXIT
csrfDangNhap=$(curl -s -c "$jarKiemThu" "$GOC/dang-nhap.php" |
  sed -n 's/.*name="_csrf" value="\([[:xdigit:]]\{64\}\)".*/\1/p' | head -1)
if [ -n "$csrfDangNhap" ]; then
  for i in 1 2 3 4 5; do
    echo "sai $i -> $(ma -d "email=hop@example.com&mat_khau=sai$i&_csrf=$csrfDangNhap" -b "$jarKiemThu" "$GOC/dang-nhap.php")"
  done
  echo "lần 6 (đúng) -> $(ma -d "email=hop@example.com&mat_khau=matkhau123&_csrf=$csrfDangNhap" -b "$jarKiemThu" "$GOC/dang-nhap.php")"
  echo "tài khoản khác -> $(ma -d "email=uyen@example.com&mat_khau=matkhau123&_csrf=$csrfDangNhap" -b "$jarKiemThu" "$GOC/dang-nhap.php")"
else
  echo "Bỏ qua ca 12: không lấy được mã CSRF từ trang đăng nhập."
fi
echo "Log (không được chứa mật khẩu):"; tail -n 5 logs/bao-mat.log 2>/dev/null
grep -cE 'matkhau|mat_khau|PHPSESSID' logs/bao-mat.log 2>/dev/null | sed 's/^/số dòng log chứa bí mật: /'

tieuDe "Ca 13 – API giỏ hàng từ chối số lượng ngoài phạm vi hoặc không phải số (mong đợi: 422)"
if [ -n "$SID" ] && [ -n "$CSRF" ]; then
  for json in '{"id":1,"sl":99}' '{"id":1,"sl":"abc"}'; do
    echo "$json -> $(ma -b "PHPSESSID=$SID" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' -d "$json" "$GOC/api/gio-hang.php")"
  done
else
  echo "Bỏ qua ca 13: cần SID và CSRF của tài khoản khách."
fi

tieuDe "Ca 14 – Tài khoản khách không được thêm môn học (mong đợi: 403)"
if [ -n "$SID" ] && [ -n "$CSRF" ]; then
  echo "khách POST thêm môn: $(ma -b "PHPSESSID=$SID" -d "ma_mon=&_csrf=$CSRF" "$GOC/quan-tri-mon-hoc.php")"
else
  echo "Bỏ qua ca 14: cần SID và CSRF của tài khoản khách."
fi

echo "Phần B của Duy: chạy php tests/kiem-thu-phan-b.php trên CSDL kiểm thử tự tạo."
tieuDe "Ca 6, 9, 11 – Kiểm thử tự động trên CSDL tạm"
"${PHP_BIN:-php}" kiemtra/kiem-thu-bt7.php --bang-3
