/** Chức năng 6: nâng cấp form POST; bỏ JS vẫn dùng action gio-hang.php.
 * Thử thêm ở danh sách/chi tiết: số món và nút thay đổi, toast xanh bên phải.
 * Dùng sự kiện ủy quyền để áp dụng cả các form tìm kiếm vừa tạo.
 */
let toastTimer;
function thongBao(message, error = false) {
    let box = document.getElementById('gio-hang-toast');
    if (!box) {
        box = document.createElement('div');
        box.id = 'gio-hang-toast';
        box.setAttribute('role', 'status');
        box.setAttribute('aria-live', 'polite');
        document.body.append(box);
    }
    box.className = `thong-bao-ke-hoach thong-bao-ke-hoach--${error ? 'loi' : 'thanh-cong'}`;
    box.textContent = message;
    box.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { box.hidden = true; }, 4500);
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-gio-hang-api]')) return;
    event.preventDefault();
    if (form.dataset.dangGui === '1') return;
    const fields = new FormData(form);
    const button = form.querySelector('button[type="submit"]');
    form.dataset.dangGui = '1';
    if (button) button.disabled = true;
    let success = false;
    try {
        const response = await fetch(form.dataset.gioHangApi, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', Accept: 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-gio-hang"]')?.content ?? ''},
            body: JSON.stringify({id: fields.get('id'), sl: fields.get('so_luong')}),
        });
        const data = await response.json();
        if (response.status !== 200) throw new Error(data.loi || 'Không thể thêm môn học.');
        if (!Number.isInteger(data.so_mon)) throw new Error('Phản hồi giỏ hàng không hợp lệ.');
        const count = document.getElementById('cart-count');
        if (count) count.textContent = String(data.so_mon);
        document.querySelectorAll('form[data-gio-hang-api]').forEach((other) => {
            if (String(new FormData(other).get('id')) !== String(fields.get('id'))) return;
            const btn = other.querySelector('button[type="submit"]');
            if (btn) {
                btn.textContent = 'Đã thêm vào kế hoạch học tập';
                btn.disabled = true;
                btn.setAttribute('aria-disabled', 'true');
                btn.classList.add(btn.classList.contains('nut-them-ke-hoach-ct')
                    ? 'nut-them-ke-hoach-ct--da-them' : 'nut-them-ke-hoach--da-them');
            }
        });
        document.dispatchEvent(new CustomEvent('ke-hoach-da-them', {detail: Number(fields.get('id'))}));
        thongBao(data.thong_bao);
        success = true;
    } catch (error) {
        thongBao(error instanceof Error ? error.message : 'Không thể kết nối máy chủ.', true);
    } finally {
        delete form.dataset.dangGui;
        if (!success && button) button.disabled = false;
    }
});
