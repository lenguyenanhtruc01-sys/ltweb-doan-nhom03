/**
 * Trang cá nhân Dương Bảo Duy — JavaScript bên ngoài (CSP: không script nội tuyến).
 *
 * 1. Đếm ngược Tết Nguyên Đán 2027.
 * 2. Đổi ngôn ngữ VI ↔ EN qua data-vi / data-en.
 * 3. Tự ẩn toast flash sau 3 giây.
 * 4. Thanh tỉ lệ thời tiết: đặt width từ data-phan-tram.
 * 5. Xác nhận trước khi xóa (data-xac-nhan) — không dùng onclick nội tuyến.
 * 6. Gửi form chọn món và oẳn tù tì bằng fetch, kèm header X-CSRF-Token.
 *
 * Mọi nội dung do máy chủ trả về đều được ghi bằng textContent, không dùng
 * innerHTML, nên dữ liệu người dùng không thể chèn HTML vào trang.
 */

/** Tạo phần tử với lớp và nội dung văn bản (an toàn, không chèn HTML). */
function taoPhanTu(tag, lop, noiDung) {
    const nut = document.createElement(tag);
    if (lop) nut.className = lop;
    if (noiDung !== undefined) nut.textContent = noiDung;
    return nut;
}

// ============================================================
// 1. ĐẾM NGƯỢC TẾT NGUYÊN ĐÁN
// ============================================================
const khungDongHo = document.getElementById('dong-ho');

if (khungDongHo) {
    const NGAY_TET = new Date('2027-02-06T00:00:00+07:00').getTime();

    const veDongHo = (nhan, giaTri) => {
        const o = taoPhanTu('div', 'dong-ho-tet__o');
        o.appendChild(taoPhanTu('span', 'dong-ho-tet__so', giaTri));
        o.appendChild(taoPhanTu('span', 'dong-ho-tet__nhan', nhan));
        return o;
    };

    function capNhatDongHo() {
        const hieu = NGAY_TET - Date.now();
        khungDongHo.replaceChildren();

        if (hieu <= 0) {
            khungDongHo.appendChild(taoPhanTu('p', 'dong-ho-tet__xong', '🎉 Chúc mừng năm mới!'));
            return;
        }

        const ngay = Math.floor(hieu / 86400000);
        const gio  = Math.floor((hieu / 3600000) % 24);
        const phut = Math.floor((hieu / 60000) % 60);
        const giay = Math.floor((hieu / 1000) % 60);

        khungDongHo.appendChild(veDongHo('Ngày', ngay));
        khungDongHo.appendChild(veDongHo('Giờ', gio));
        khungDongHo.appendChild(veDongHo('Phút', phut));
        khungDongHo.appendChild(veDongHo('Giây', giay));
    }

    capNhatDongHo();
    setInterval(capNhatDongHo, 1000);
}

// ============================================================
// 2. ĐỔI NGÔN NGỮ VI ↔ EN
// ============================================================
const nutNgonNgu  = document.getElementById('nut-doi-ngon-ngu');
const nhanNgonNgu = document.getElementById('nhan-ngon-ngu');

if (nutNgonNgu && nhanNgonNgu) {
    let ngonNgu = 'vi';
    try {
        ngonNgu = localStorage.getItem('ngonNguDuy') === 'en' ? 'en' : 'vi';
    } catch (loi) {
        ngonNgu = 'vi';
    }

    function apDungNgonNgu() {
        document.querySelectorAll('[data-vi][data-en]').forEach((el) => {
            const noiDungMoi = el.getAttribute('data-' + ngonNgu);
            if (noiDungMoi) el.textContent = noiDungMoi;
        });
        document.documentElement.lang = ngonNgu;
        nhanNgonNgu.textContent = ngonNgu === 'vi' ? 'EN' : 'VI';
    }

    nutNgonNgu.addEventListener('click', () => {
        ngonNgu = ngonNgu === 'vi' ? 'en' : 'vi';
        try {
            localStorage.setItem('ngonNguDuy', ngonNgu);
        } catch (loi) {
            /* Trình duyệt chặn localStorage: vẫn đổi ngôn ngữ trong trang. */
        }
        apDungNgonNgu();
    });

    apDungNgonNgu();
}

// ============================================================
// 3. TỰ ẨN TOAST FLASH SAU 3 GIÂY
// ============================================================
const toast = document.querySelector('.thong-bao-thanh-cong');
if (toast) {
    const dongToast = () => {
        toast.classList.add('thong-bao-thanh-cong--an');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    const henGio = setTimeout(dongToast, 3000);
    const nutDong = toast.querySelector('.thong-bao-thanh-cong__dong');
    if (nutDong) {
        nutDong.addEventListener('click', () => {
            clearTimeout(henGio);
            dongToast();
        });
    }
}

// ============================================================
// 4. THANH TỈ LỆ THỜI TIẾT (CSP không cho style nội tuyến)
// ============================================================
document.querySelectorAll('.thoi-tiet__thanh-do[data-phan-tram]').forEach((thanh) => {
    const phanTram = Number.parseFloat(thanh.dataset.phanTram);
    if (Number.isFinite(phanTram) && phanTram >= 0) {
        thanh.style.width = Math.min(100, phanTram) + '%';
    }
});

// ============================================================
// 5. XÁC NHẬN TRƯỚC KHI XÓA — thay cho onclick="return confirm(...)"
// ============================================================
document.querySelectorAll('[data-xac-nhan]').forEach((phanTu) => {
    const bieuMau = phanTu.closest('form');
    if (!bieuMau) return;
    bieuMau.addEventListener('submit', (suKien) => {
        if (!window.confirm(phanTu.dataset.xacNhan)) {
            suKien.preventDefault();
        }
    });
});

// ============================================================
// 6. GỬI FORM BẰNG FETCH, KÈM HEADER X-CSRF-TOKEN
// ============================================================
function maCsrf() {
    const theMeta = document.querySelector('meta[name="csrf-token"]');
    return theMeta ? theMeta.content : '';
}

/** Gửi form POST bằng fetch; trả về JSON của máy chủ. */
async function guiAjax(bieuMau, themVao) {
    const duLieu = new FormData(bieuMau);
    Object.entries(themVao || {}).forEach(([khoa, giaTri]) => duLieu.append(khoa, giaTri));

    const phanHoi = await fetch(bieuMau.action || 'gioithieu.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': maCsrf(),
            'Accept': 'application/json',
        },
        body: duLieu,
        credentials: 'same-origin',
    });

    const kieuNoiDung = phanHoi.headers.get('content-type') || '';
    if (!kieuNoiDung.includes('application/json')) {
        throw new Error(await phanHoi.text());
    }
    return phanHoi.json();
}

/** Hiện lỗi cạnh ô nhập hoặc trên đầu khối kết quả. */
function hienLoi(bieuMau, khoiLoi, tapLoi) {
    bieuMau.querySelectorAll('.loi-bieu-mau').forEach((el) => el.remove());
    if (!khoiLoi) return;
    khoiLoi.replaceChildren();
    Object.values(tapLoi || {}).forEach((thongDiep) => {
        khoiLoi.appendChild(taoPhanTu('p', 'loi-bieu-mau', String(thongDiep)));
    });
}

// ---------- 6a. Máy chọn món ----------
const formChonMon = document.getElementById('form-chon-mon');
if (formChonMon) {
    const khungKetQua = document.getElementById('chon-mon-ket-qua');
    formChonMon.addEventListener('submit', async (suKien) => {
        suKien.preventDefault();
        let duLieu;
        try {
            duLieu = await guiAjax(formChonMon);
        } catch (loi) {
            hienLoi(formChonMon, khungKetQua, { chung: 'Không gửi được biểu mẫu. Hãy tải lại trang rồi thử lại.' });
            return;
        }

        if (!duLieu.ok) {
            hienLoi(formChonMon, khungKetQua, duLieu.loi || {});
            return;
        }

        hienLoi(formChonMon, khungKetQua, {});
        const khoi = taoPhanTu('div', 'chon-mon__ket-qua');
        khoi.setAttribute('role', 'status');
        khoi.setAttribute('aria-live', 'polite');
        khoi.appendChild(taoPhanTu('p', 'chon-mon__nhan', 'Hôm nay ăn gì?'));
        khoi.appendChild(taoPhanTu('p', 'chon-mon__mon', duLieu.mon));
        khoi.appendChild(taoPhanTu(
            'p',
            'chon-mon__meta',
            'Bữa: ' + duLieu.buaLabel + ' — lúc ' + duLieu.thoiGian
        ));
        khungKetQua.replaceChildren(khoi);
    });
}

// ---------- 6b. Oẳn tù tì ----------
const formOtt = document.getElementById('form-oan-tu-ti');
if (formOtt) {
    formOtt.addEventListener('submit', async (suKien) => {
        if (!suKien.submitter || suKien.submitter.name !== 'chon') return;
        suKien.preventDefault();

        let duLieu;
        try {
            duLieu = await guiAjax(formOtt, { chon: suKien.submitter.value });
        } catch (loi) {
            return;
        }
        if (!duLieu.ok) return;

        const khungVua = document.getElementById('oan-tu-ti-vua');
        const khoiVua = taoPhanTu('div', 'oan-tu-ti__ket-qua oan-tu-ti__ket-qua--' + duLieu.vua.ketQua);
        khoiVua.setAttribute('role', 'status');
        khoiVua.setAttribute('aria-live', 'polite');
        khoiVua.appendChild(taoPhanTu(
            'p', '', 'Bạn: ' + duLieu.vua.nguoiLabel + ' vs Máy: ' + duLieu.vua.mayLabel
        ));
        khoiVua.appendChild(taoPhanTu('p', 'oan-tu-ti__vua', duLieu.vua.ketQuaLabel));
        khungVua.replaceChildren(khoiVua);

        const thongKe = duLieu.thongKe;
        const khungThongKe = document.getElementById('oan-tu-ti-thong-ke');
        const khoiThongKe = taoPhanTu('div', 'oan-tu-ti__thong-ke');
        khoiThongKe.appendChild(taoPhanTu('h3', '', 'Thống kê (Lưu DB)'));
        const danhSach = taoPhanTu('ul');
        [
            ['🎉 Thắng: ', thongKe.thang],
            ['😢 Thua: ', thongKe.thua],
            ['🤝 Hoà: ', thongKe.hoa],
        ].forEach(([nhan, so]) => {
            const muc = taoPhanTu('li', '', nhan);
            muc.appendChild(taoPhanTu('strong', '', String(so)));
            danhSach.appendChild(muc);
        });
        const mucTong = taoPhanTu('li', '', 'Tổng: ');
        mucTong.appendChild(taoPhanTu('strong', '', String(thongKe.tong)));
        mucTong.appendChild(document.createTextNode(' ván — Tỉ lệ thắng: '));
        mucTong.appendChild(taoPhanTu('strong', '', String(thongKe.tiLe) + '%'));
        danhSach.appendChild(mucTong);
        khoiThongKe.appendChild(danhSach);
        khungThongKe.replaceChildren(khoiThongKe);

        const khungLichSu = document.getElementById('oan-tu-ti-lich-su');
        if (Array.isArray(duLieu.lichSu) && duLieu.lichSu.length) {
            const bang = taoPhanTu('table', 'oan-tu-ti__bang');
            const thead = taoPhanTu('thead');
            const hangDau = taoPhanTu('tr');
            ['Thời gian', 'Bạn', 'Máy', 'Kết quả'].forEach((nhan) => {
                hangDau.appendChild(taoPhanTu('th', '', nhan));
            });
            thead.appendChild(hangDau);
            bang.appendChild(thead);
            const tbody = taoPhanTu('tbody');
            duLieu.lichSu.forEach((van) => {
                const hang = taoPhanTu('tr');
                [van.thoiGian, van.nguoiLabel, van.mayLabel, van.ketQuaLabel].forEach((o) => {
                    hang.appendChild(taoPhanTu('td', '', o));
                });
                tbody.appendChild(hang);
            });
            bang.appendChild(tbody);
            khungLichSu.replaceChildren(taoPhanTu('h3', '', '10 ván gần nhất'), bang);
        }
    });
}
