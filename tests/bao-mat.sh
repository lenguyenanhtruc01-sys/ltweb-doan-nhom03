#!/usr/bin/env bash
# tests/bao-mat.sh — Bảng 3 (Bài tập nhóm 7): kiểm thử phủ định, chạy lại sau mỗi lần sửa mã.
# Người phụ trách: Lê Dương Hoàng Hải (3120224042). Chạy trong Git Bash: bash tests/bao-mat.sh
# Không ghi giá trị mã phiên / mã CSRF vào tệp: điền vào biến môi trường khi chạy, ví dụ
#   SID=<PHPSESSID của tài khoản khách> CSRF=<thẻ meta csrf-token> bash tests/bao-mat.sh
# GOC: php -S localhost:8000 (mặc định) hoặc http://localhost/ltweb-doan-nhom05 (Apache, ca 10).
GOC="${GOC:-http://localhost:8000}"
APACHE="${APACHE:-http://localhost/ltweb-doan-nhom03}"
SID="${SID:-}"
CSRF="${CSRF:-}"
ma() { curl -s -g -o /dev/null -w '%{http_code}' "$@"; }
tieuDe() { echo; echo "=== $1"; }

tieuDe "Ca 1 – Header và cookie (mong đợi: CSP, nosniff, Referrer-Policy; không X-Powered-By; HttpOnly; SameSite=Lax)"
curl -s -I "$GOC/index.php" | grep -iE '^(HTTP|content-security|x-content|referrer|x-powered|set-cookie)'

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
curl -s -o /dev/null -D - "$GOC/xoa-mon-hoc.php?id=1" | grep -iE '^(HTTP|allow)'
[ -n "$SID" ] && echo "khách POST xóa: $(ma -b "PHPSESSID=$SID" -d "id=1&_csrf=$CSRF" "$GOC/xoa-mon-hoc.php")"

tieuDe "Ca 5 – Cố định phiên (mong đợi: Set-Cookie trả mã MỚI, khác tudat123)"
curl -s -I -b 'PHPSESSID=tudat1234567890' "$GOC/index.php" | grep -i '^set-cookie'

tieuDe "Ca 6 – Hết hạn phiên: làm tay (đổi 1800 thành 60 trong inc/config.php, chờ > 1 phút)"

tieuDe "Ca 7 – XSS (mong đợi: &lt;b&gt;ao&lt;/b&gt; trong mã nguồn)"
curl -s "$GOC/danh-sach.php?q=%3Cb%3Eao%3C%2Fb%3E" | grep -o '&lt;b&gt;ao&lt;/b&gt;' | head -1

tieuDe "Ca 8 – Tham số dạng mảng (mong đợi: 200/404, không 500)"
for p in 'danh-sach.php?q[]=ao' 'chi-tiet.php?id[]=1' 'danh-sach.php?sx[]=x'; do echo "$p -> $(ma "$GOC/$p")"; done
[ -n "$CSRF" ] && echo "dang-nhap email[]: $(ma -b "PHPSESSID=$SID" -d "email[]=a&mat_khau[]=b&_csrf=$CSRF" "$GOC/dang-nhap.php")"
[ -n "$CSRF" ] && echo "lien-he hoten[]:   $(ma -b "PHPSESSID=$SID" -d "hoten[]=a&email[]=b&_csrf=$CSRF" "$GOC/lien-he.php")"

tieuDe "Ca 9 – .env (mong đợi: hai lệnh git không in gì)"
git ls-files .env; git log --all --oneline -- .env; echo "(hết)"

tieuDe "Ca 10 – .htaccess trên Apache (mong đợi: 404 cho .env/.git/inc/vendor; 403 cho .sql/.log/composer.json/uploads; CSS 200)"
for p in .env .git/config inc/db.php vendor/autoload.php database/schema.sql logs/bao-mat.log composer.json uploads/ css/style.css; do
  echo "$p -> $(ma "$APACHE/$p")"
done

tieuDe "Ca 11 – Quyền tối thiểu: chạy trong Shell XAMPP: mysql -u nhom03_app -p ltweb_nhom03 -e 'DROP TABLE mon_hoc' (mong đợi 1142)"

tieuDe "Ca 12 – Đoán mật khẩu (mong đợi: 5 lần 200, lần 6 đúng mật khẩu vẫn 429; email khác 303)"
EMAIL="${EMAIL:-hop@example.com}"
if [ -n "$CSRF" ]; then
  for i in 1 2 3 4 5; do echo "sai $i -> $(ma -d "email=$EMAIL&mat_khau=sai$i&_csrf=$CSRF" -b "PHPSESSID=$SID" "$GOC/dang-nhap.php")"; done
  echo "lần 6 (đúng) -> $(ma -d "email=$EMAIL&mat_khau=matkhau123&_csrf=$CSRF" -b "PHPSESSID=$SID" "$GOC/dang-nhap.php")"
  echo "email khác -> $(ma -d "email=uyen@example.com&mat_khau=matkhau123&_csrf=$CSRF" -b "PHPSESSID=$SID" "$GOC/dang-nhap.php")"
fi
echo "Log (không được chứa mật khẩu):"; tail -n 5 logs/bao-mat.log 2>/dev/null
grep -cE 'matkhau|mat_khau|PHPSESSID' logs/bao-mat.log 2>/dev/null | sed 's/^/số dòng log chứa bí mật: /'
