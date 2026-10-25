/** Tìm kiếm tức thời có debounce 350ms; giữ lọc/sắp xếp/phân trang của GET.
 * Thử gõ nhanh, đổi bộ lọc, chuyển trang; phản hồi cũ không ghi đè kết quả mới.
 * Dữ liệu API tạo bằng DOM và textContent, không dùng innerHTML.
 */
const form = document.querySelector('form[data-tim-kiem-api]');
const grid = document.getElementById('danh-sach-ket-qua');
const pager = document.getElementById('phan-trang-api');
const status = document.getElementById('tim-kiem-status');
let timer;
let controller;
let generation = 0;
const added = new Set();
document.addEventListener('ke-hoach-da-them', (event) => added.add(event.detail));

function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
}

function card(mon, selected) {
    const article = element('article', undefined, 'the-san-pham');
    // Chỉ cho ảnh cùng nguồn; không nhận URL javascript/data từ API.
    if (mon.anh) {
        const url = new URL(mon.anh, location.href);
        if (url.origin === location.origin && ['http:', 'https:'].includes(url.protocol)) {
            const img = element('img');
            img.src = url.href; img.alt = mon.tenMon; img.width = 400; img.height = 250; img.loading = 'lazy';
            article.append(img);
        }
    }
    const body = element('div', undefined, 'the-san-pham__than');
    body.append(element('h3', mon.tenMon), element('p', `Mã môn: ${mon.maMon}`),
        element('p', `Số tín chỉ: ${mon.soTinChi}`));
    if (mon.trangThai) body.append(element('p', `Trạng thái: ${mon.trangThai}`));
    const actions = element('div', undefined, 'the-san-pham__hanh-dong');
    const link = element('a', 'Xem chi tiết →', 'the-san-pham__lien-ket');
    link.href = `chi-tiet.php?id=${encodeURIComponent(mon.id)}`;
    const linkRow = element('div', undefined, 'the-san-pham__o-xanh');
    const favorite = element('button', undefined, 'nut-yeu-thich');
    favorite.type = 'button'; favorite.dataset.yeuThichId = String(mon.id);
    favorite.setAttribute('aria-pressed', 'false'); favorite.setAttribute('aria-label', 'Thêm vào yêu thích');
    const icon = element('span', '♡', 'nut-yeu-thich__icon'); icon.setAttribute('aria-hidden', 'true');
    const favoriteLabel = element('span', 'Thêm vào yêu thích', 'visually-hidden');
    favoriteLabel.dataset.nhanYeuThich = '';
    favorite.append(icon, favoriteLabel); linkRow.append(link, favorite); actions.append(linkRow);
    const addForm = element('form', undefined, 'form-them-ke-hoach');
    addForm.method = 'post'; addForm.action = 'gio-hang.php';
    addForm.dataset.gioHangApi = 'api/gio-hang.php';
    Object.entries({quay_lai: 'danh-sach.php', hanh_dong: 'them', id: mon.id, so_luong: 1}).forEach(([name, value]) => {
        const input = element('input'); input.type = 'hidden'; input.name = name; input.value = String(value);
        addForm.append(input);
    });
    const button = element('button', selected ? 'Đã thêm vào kế hoạch học tập' : 'Thêm vào kế hoạch học tập',
        `nut-them-ke-hoach${selected ? ' nut-them-ke-hoach--da-them' : ''}`);
    button.type = 'submit'; button.disabled = selected;
    if (selected) button.setAttribute('aria-disabled', 'true');
    addForm.append(button); actions.append(addForm); body.append(actions); article.append(body);
    return article;
}

async function search(page = 1) {
    const current = ++generation;
    controller?.abort(); controller = new AbortController();
    const params = new URLSearchParams(new FormData(form));
    params.set('trang', String(page));
    status.textContent = 'Đang tìm kiếm…';
    grid.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(`${form.dataset.timKiemApi}?${params}`, {
            signal: controller.signal, headers: {Accept: 'application/json'}, credentials: 'same-origin',
        });
        const data = await response.json();
        if (current !== generation) return;
        if (!response.ok) throw new Error(data.loi || 'Không thể tìm kiếm.');
        if (!Array.isArray(data.du_lieu)) throw new Error('Phản hồi tìm kiếm không hợp lệ.');
        const chosen = new Set([...data.da_them, ...added]);
        const fragment = document.createDocumentFragment();
        data.du_lieu.forEach((mon) => fragment.append(card(mon, chosen.has(mon.id))));
        if (!data.du_lieu.length) fragment.append(element('p', 'Không tìm thấy môn học phù hợp.', 'thong-bao-khong-co'));
        grid.replaceChildren(fragment);
        document.getElementById('ket-qua-tim-kiem').textContent = `Kết quả tìm kiếm (${data.tong} môn)`;
        pager.replaceChildren();
        if (data.so_trang > 1) {
            const nav = element('nav'); nav.setAttribute('aria-label', 'Phân trang');
            const list = element('ul', undefined, 'pagination');
            list.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0';
            for (let i = 1; i <= data.so_trang; i++) {
                const p = new URLSearchParams(params); p.set('trang', String(i));
                const a = element('a', String(i)); a.href = `danh-sach.php?${p}`; a.dataset.trangApi = String(i);
                a.style.cssText = 'display:block;padding:8px 14px;border:1px solid #ddd;border-radius:4px';
                if (i === data.trang) a.setAttribute('aria-current', 'page');
                const li = element('li'); li.append(a); list.append(li);
            }
            nav.append(list); pager.append(nav);
        }
        params.set('trang', String(data.trang));
        history.replaceState(null, '', `danh-sach.php?${params}`);
        status.textContent = `Tìm thấy ${data.tong} môn học.`;
    } catch (error) {
        if (current === generation && error.name !== 'AbortError') status.textContent = `${error.message} Bạn có thể dùng nút Lọc kết quả để tải lại trang.`;
    } finally {
        if (current === generation) grid.removeAttribute('aria-busy');
    }
}

if (form && grid && pager && status) {
    form.addEventListener('input', () => {
        clearTimeout(timer); ++generation; controller?.abort();
        timer = setTimeout(() => search(1), 350);
    });
    form.addEventListener('submit', (event) => { event.preventDefault(); clearTimeout(timer); search(1); });
    pager.addEventListener('click', (event) => {
        const a = event.target.closest('a');
        if (!a || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault(); clearTimeout(timer);
        search(Number(new URL(a.href).searchParams.get('trang')) || 1);
    });
}
