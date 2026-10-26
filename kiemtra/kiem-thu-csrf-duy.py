"""Chạy trên bản sao/CSDL thử: python kiemtra/kiem-thu-csrf-duy.py URL_GOC.
Khách có token vẫn chọn món/chơi/check-in; token sai không đổi dữ liệu dùng chung.
"""
import http.cookiejar
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

sys.stdout.reconfigure(encoding='utf-8')
base = sys.argv[1].rstrip('/') + '/'
path = 'thanhvien/3120224034_Duy/gioithieu.php'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def read(fields=None):
    data = urllib.parse.urlencode(fields).encode() if fields is not None else None
    try:
        response = client.open(base + path, data, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode('utf-8')

status, html = read()
assert status == 200
actions = [
    {'hanh_dong': 'chon_mon', 'dsMon': 'CSRF_TEST_A\nCSRF_TEST_B', 'bua': 'sang'},
    {'hanh_dong': 'choi_oan_tu_ti', 'chon': 'keo'},
    {'hanh_dong': 'check_in_thoi_tiet', 'trang_thai': 'nang'},
]
for action in actions:
    for token in ({}, {'_csrf': 'sai'}, {'_csrf[]': 'sai'}):
        status, _ = read(action | token)
        assert status == 403, (action['hanh_dong'], token, status)
print('PASS thiếu/sai/token mảng bị chặn ở cả ba thao tác POST. Cần đối chiếu CSDL trong bản thử để kiểm tra không ghi.');
status, html = read()
tokens = re.findall(r'name="_csrf" value="([^"]+)"', html)
assert len(tokens) == 3 and len(set(tokens)) == 1, 'Cả ba form phải có token.'
for action in actions:
    status, result = read(action | {'_csrf': tokens[0]})
    assert status == 200
    if action['hanh_dong'] == 'chon_mon':
        assert 'CSRF_TEST_A' in result
other = urllib.request.urlopen(base + path).read().decode('utf-8')
assert 'CSRF_TEST_A' not in other, 'Khách đã ghi món riêng vào danh sách dùng chung.'
print('PASS token đúng: chọn món, chơi, check-in và PRG; danh sách riêng của khách không lộ sang phiên khác.')
