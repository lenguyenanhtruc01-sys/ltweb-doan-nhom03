/**
 * Module 1: Đếm ngược Tết Nguyên Đán 2027 (06/02/2027).
 * Module 2: Đổi ngôn ngữ VI ↔ EN qua data-vi / data-en.
 * Module 3: Tự ẩn toast flash sau 3 giây.
 * Module 4: AJAX cho 2 chức năng Phần B (chọn món + oẳn tù tì)
 *           — không reload trang; tắt JS vẫn fallback POST thường.
 */

// ============================================================
// MODULE 1 — ĐẾM NGƯỢC TẾT NGUYÊN ĐÁN
// ============================================================
const khungDongHo = document.getElementById('dong-ho');

if (khungDongHo) {
    const NGAY_TET = new Date('2027-02-06T00:00:00+07:00').getTime();

    function capNhatDongHo() {
        const hieu = NGAY_TET - Date.now();

        if (hieu <= 0) {
            khungDongHo.innerHTML = '<p class="dong-ho-tet__xong">🎉 Chúc mừng năm mới!</p>';
            return;
        }

        const ngay = Math.floor(hieu / 86400000);
        const gio  = Math.floor((hieu / 3600000) % 24);
        const phut = Math.floor((hieu / 60000) % 60);
        const giay = Math.floor((hieu / 1000) % 60);

        khungDongHo.innerHTML = `
            <div class="dong-ho-tet__o">
                <span class="dong-ho-tet__so">${ngay}</span>
                <span class="dong-ho-tet__nhan">Ngày</span>
            </div>
            <div class="dong-ho-tet__o">
                <span class="dong-ho-tet__so">${gio}</span>
                <span class="dong-ho-tet__nhan">Giờ</span>
            </div>
            <div class="dong-ho-tet__o">
                <span class="dong-ho-tet__so">${phut}</span>
                <span class="dong-ho-tet__nhan">Phút</span>
            </div>
            <div class="dong-ho-tet__o">
                <span class="dong-ho-tet__so">${giay}</span>
                <span class="dong-ho-tet__nhan">Giây</span>
            </div>
        `;
    }

    capNhatDongHo();
    setInterval(capNhatDongHo, 1000);
}

// ============================================================
// MODULE 2 — ĐỔI NGÔN NGỮ VI ↔ EN
// ============================================================
const nutNgongNgu  = document.getElementById('nut-doi-ngon-ngu');
const nhanNgongNgu = document.getElementById('nhan-ngon-ngu');

if (nutNgongNgu && nhanNgongNgu) {
    let ngonNgu = localStorage.getItem('ngonNguDuy') || 'vi';

    function apDungNgonNgu() {
        document.querySelectorAll('[data-vi][data-en]').forEach(el => {
            const text = el.getAttribute('data-' + ngonNgu);
            if (text) el.textContent = text;
        });
        document.documentElement.lang = ngonNgu;
        nhanNgongNgu.textContent = ngonNgu === 'vi' ? 'EN' : 'VI';
    }

    nutNgongNgu.addEventListener('click', () => {
        ngonNgu = ngonNgu === 'vi' ? 'en' : 'vi';
        localStorage.setItem('ngonNguDuy', ngonNgu);
        apDungNgonNgu();
    });

    apDungNgonNgu();
}

// ============================================================
// MODULE 3 — TỰ ẨN TOAST FLASH SAU 3 GIÂY
// ============================================================
const toast = document.querySelector('.thong-bao-thanh-cong');
if (toast) {
    const dong = () => {
        toast.classList.add('thong-bao-thanh-cong--an');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    const timer = setTimeout(dong, 3000);
    const nutDong = toast.querySelector('.thong-bao-thanh-cong__dong');
    if (nutDong) {
        nutDong.addEventListener('click', () => {
            clearTimeout(timer);
            dong();
        });
    }
}

// ============================================================
// MODULE 4 — AJAX CHO 2 CHỨC NĂNG PHẦN B
// ============================================================

/** Escape HTML để chống XSS khi chèn vào innerHTML. */
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Gửi form qua fetch, trả về JSON.
 * @param {HTMLFormElement} formEl
 * @param {Object} extra - Các cặp key/value append thêm vào FormData
 *                         (dùng cho nút submit có name vì FormData
 *                          không tự lấy value của nút được bấm).
 */
async function guiAjax(formEl, extra = {}) {
    const fd = new FormData(formEl);
    for (const [k, v] of Object.entries(extra)) {
        fd.append(k, v);
    }
    const res = await fetch(formEl.action || 'gioithieu.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
}

// ---------- Chức năng 1: máy chọn món ----------
const formChonMon = document.getElementById('form-chon-mon');
if (formChonMon) {
    formChonMon.addEventListener('submit', async (e) => {
        e.preventDefault();

        let data;
        try {
            data = await guiAjax(formChonMon);
        } catch (err) {
            console.error(err);
            return;
        }

        // Xoá lỗi cũ
        formChonMon.querySelectorAll('.loi-bieu-mau').forEach(el => el.remove());

        if (!data.ok) {
            for (const [khoa, msg] of Object.entries(data.loi || {})) {
                const input = formChonMon.querySelector(`[name="${khoa}"]`);
                if (!input) continue;
                const small = document.createElement('small');
                small.className = 'loi-bieu-mau';
                small.textContent = msg;
                input.insertAdjacentElement('afterend', small);
            }
            return;
        }

        // Cập nhật khối kết quả
        const khungKetQua = document.getElementById('chon-mon-ket-qua');
        khungKetQua.innerHTML = `
            <div class="chon-mon__ket-qua" role="status" aria-live="polite">
                <p class="chon-mon__nhan">Hôm nay ăn gì?</p>
                <p class="chon-mon__mon">${escapeHtml(data.mon)}</p>
                <p class="chon-mon__meta">
                    Bữa: <strong>${escapeHtml(data.buaLabel)}</strong>
                    — lúc ${escapeHtml(data.thoiGian)}
                </p>
            </div>
        `;

        // Cập nhật lịch sử
        const khungLs = document.getElementById('chon-mon-lich-su');
        if (data.lichSu && data.lichSu.length) {
            khungLs.innerHTML = `
                <h3>5 lần chọn gần nhất</h3>
                <ul class="chon-mon__lich-su">
                    ${data.lichSu.map(m => `
                        <li>
                            <strong>${escapeHtml(m.mon)}</strong>
                            — bữa ${escapeHtml(m.buaLabel)}
                            <em>(${escapeHtml(m.thoiGian)})</em>
                        </li>
                    `).join('')}
                </ul>
                <form method="post" action="gioithieu.php" style="margin-top:.75rem;">
                    <input type="hidden" name="hanh_dong" value="xoa_lich_su_mon">
                    <button type="submit" class="nut-phu"
                            onclick="return confirm('Xoá toàn bộ lịch sử chọn món?');">
                        🗑 Xoá lịch sử
                    </button>
                </form>
            `;
        }
    });
}

// ---------- Chức năng 2: oẳn tù tì ----------
const formOtt = document.getElementById('form-oan-tu-ti');
if (formOtt) {
    formOtt.addEventListener('submit', async (e) => {
        // Chỉ chặn khi bấm nút có name="chon"
        if (!e.submitter || e.submitter.name !== 'chon') return;
        e.preventDefault();

        const chon = e.submitter.value; // "keo" | "bua" | "bao"

        let data;
        try {
            data = await guiAjax(formOtt, { chon });
        } catch (err) {
            console.error(err);
            return;
        }

        if (!data.ok) return;

        const lop = data.vua.ketQua || 'hoa';

        // Cập nhật ván vừa chơi
        document.getElementById('oan-tu-ti-vua').innerHTML = `
            <div class="oan-tu-ti__ket-qua oan-tu-ti__ket-qua--${lop}"
                 role="status" aria-live="polite">
                <p>
                    Bạn: <strong>${escapeHtml(data.vua.nguoiLabel)}</strong>
                    &nbsp;vs&nbsp;
                    Máy: <strong>${escapeHtml(data.vua.mayLabel)}</strong>
                </p>
                <p class="oan-tu-ti__vua">${escapeHtml(data.vua.ketQuaLabel)}</p>
            </div>
        `;

        // Cập nhật thống kê
        const tk = data.thongKe;
        document.getElementById('oan-tu-ti-thong-ke').innerHTML = `
            <div class="oan-tu-ti__thong-ke">
                <h3>Thống kê</h3>
                <ul>
                    <li>🎉 Thắng: <strong>${tk.thang}</strong></li>
                    <li>😢 Thua: <strong>${tk.thua}</strong></li>
                    <li>🤝 Hoà: <strong>${tk.hoa}</strong></li>
                    <li>Tổng: <strong>${tk.tong}</strong> ván —
                        Tỉ lệ thắng: <strong>${tk.tiLe}%</strong></li>
                </ul>
                ${tk.tong > 0 ? `
                    <div class="oan-tu-ti__bar" aria-hidden="true">
                        <div class="oan-tu-ti__bar-thang"
                             style="width: ${tk.tiLe}%;"></div>
                    </div>
                ` : ''}
            </div>
        `;

        // Cập nhật bảng lịch sử
        const khungLs = document.getElementById('oan-tu-ti-lich-su');
        if (data.lichSu && data.lichSu.length) {
            khungLs.innerHTML = `
                <h3>10 ván gần nhất</h3>
                <table class="oan-tu-ti__bang">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Bạn</th>
                            <th>Máy</th>
                            <th>Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.lichSu.map(v => `
                            <tr>
                                <td>${escapeHtml(v.thoiGian)}</td>
                                <td>${escapeHtml(v.nguoiLabel)}</td>
                                <td>${escapeHtml(v.mayLabel)}</td>
                                <td>${escapeHtml(v.ketQuaLabel)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <form method="post" action="gioithieu.php" style="margin-top:.75rem;">
                    <input type="hidden" name="hanh_dong" value="reset_oan_tu_ti">
                    <button type="submit" class="nut-phu"
                            onclick="return confirm('Reset toàn bộ thống kê oẳn tù tì?');">
                        🔄 Reset thống kê
                    </button>
                </form>
            `;
        }
    });
}
