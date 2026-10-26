"""Chạy với URL gốc làm tham số; chỉ dùng giỏ khách trong session thử riêng."""
import http.cookiejar
import json
import sys
import urllib.parse
import urllib.request
from html.parser import HTMLParser

sys.stdout.reconfigure(encoding='utf-8')
base = sys.argv[1].rstrip('/') + '/'
client = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def read(path, data=None):
    request = urllib.request.Request(base + path, data=(
        urllib.parse.urlencode(data).encode() if data is not None else None))
    with client.open(request, timeout=10) as response:
        return response.read().decode('utf-8')


def cart():
    return json.loads(read('api/gio-hang.php'))


class Forms(HTMLParser):
    def __init__(self):
        super().__init__()
        self.current = None
        self.carts = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'form':
            self.current = {} if attrs.get('action') == 'gio-hang.php' else None
        if tag == 'input' and self.current is not None:
            self.current[attrs.get('name')] = attrs.get('value')

    def handle_endtag(self, tag):
        if tag == 'form' and self.current is not None:
            self.carts.append(self.current)
            self.current = None


state = cart()
assert state['so_mon'] == 0
token = state['csrf']
read('gio-hang.php', {'hanh_dong': 'them', 'id': '1', 'so_luong': '1', '_csrf': token})
assert cart()['so_mon'] > 0, 'Token đúng không thêm được môn thử ID 1.'
before = cart()['du_lieu']
for endpoint in ['gio-hang.php', 'xu-ly-gio-hang.php']:
    for action in ['them', 'cap_nhat', 'xoa', 'xoa_het']:
        for invalid in [{}, {'_csrf': 'sai-token'}, {'_csrf[]': token}]:
            fields = {'hanh_dong' if endpoint == 'gio-hang.php' else 'action': action,
                      'id': '1', 'so_luong': '2', **invalid}
            html = read(endpoint, fields)
            assert 'Phiên giỏ hàng không hợp lệ' in html
            assert cart()['du_lieu'] == before, (endpoint, action, invalid)
print('PASS thiếu/sai/token dạng mảng: cả 4 thao tác và URL cũ giữ nguyên giỏ.')

for path in ['danh-sach.php', 'chi-tiet.php?id=1', 'gio-hang.php']:
    parser = Forms()
    parser.feed(read(path))
    assert parser.carts, path
    assert all(form.get('_csrf') == token for form in parser.carts), path
print('PASS form danh sách, chi tiết, cập nhật, xóa và xóa hết đều có token.')

read('gio-hang.php', {'hanh_dong': 'cap_nhat', 'id': '1', 'so_luong': '2', '_csrf': token})
assert cart()['du_lieu'] != before
read('gio-hang.php', {'hanh_dong': 'xoa', 'id': '1', '_csrf': token})
assert cart()['so_mon'] == 0
read('xu-ly-gio-hang.php', {'action': 'them', 'id': '1', 'so_luong': '1', '_csrf': token})
assert cart()['so_mon'] > 0
read('gio-hang.php', {'hanh_dong': 'xoa_het', '_csrf': token})
assert cart()['so_mon'] == 0
print('PASS token hợp lệ: thêm/sửa/xóa/xóa hết và URL cũ hoạt động.')
