// Chạy: node kiemtra/kiem-thu-anh-tim-kiem.cjs. Kiểm tra card thực tế, không gọi mạng.
const {readFileSync} = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const node = tag => ({tag, children: [], dataset: {}, append(...items) { this.children.push(...items); }, setAttribute() {}});
const context = vm.createContext({URL, location: {href: 'http://localhost/ltweb-doan-nhom03/danh-sach.php', origin: 'http://localhost'},
    document: {querySelector: () => null, getElementById: () => null, addEventListener() {}, createElement: node}});
vm.runInContext(readFileSync(require('node:path').join(__dirname, '../js/tim-kiem.js'), 'utf8'), context);
for (const [anh, expected] of [
    ['WEB301.png', 'images/WEB301.png'], ['images/WEB301.png', 'images/WEB301.png'],
    ['uploads/anh_test.png', 'uploads/anh_test.png'], ['/images/WEB301.png', '/images/WEB301.png'],
    ['https://example.invalid/test.png', null], ['', null],
]) {
    context.mon = {id: 1, maMon: 'WEB301', tenMon: 'Web', soTinChi: 3, anh};
    const result = vm.runInContext('card(mon, false)', context);
    const img = result.children.find(child => child.tag === 'img');
    assert.equal(img?.src ?? null, expected === null ? null : new URL(expected, context.location.href).href, anh);
}
console.log('PASS ảnh seed/upload/root-relative và chặn ảnh khác nguồn trong card tìm kiếm.');
