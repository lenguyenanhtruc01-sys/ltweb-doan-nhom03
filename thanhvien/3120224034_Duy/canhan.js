// js/canhan.js — Trang cá nhân Dương Bảo Duy — Nhóm 3 — EduGPA
// Tương tác 1: Đồng hồ đếm ngược đến Tết Đinh Mùi 2027 (06/02/2027), cập nhật mỗi giây.
// Tương tác 2: Đổi ngôn ngữ VI/EN cho toàn bộ tiêu đề và nội dung, lưu bằng localStorage.
// Thử: Mở trang → đồng hồ chạy từng giây.
// Thử: Bấm nút "🌐 EN" → toàn bộ tiêu đề chuyển sang tiếng Anh, reload vẫn giữ.

// =========================================================
// TƯƠNG TÁC 1: ĐỒNG HỒ ĐẾM NGƯỢC TẾT NGUYÊN ĐÁN
// =========================================================

// Mùng 1 Tết Đinh Mùi 2027: 06/02/2027 (giờ VN, GMT+7)
const NGAY_TET = new Date("2027-02-06T00:00:00+07:00").getTime();

const khungDongHo = document.querySelector("#dong-ho");

/**
 * Tính khoảng cách từ hiện tại đến ngày Tết.
 * Trả về { ngay, gio, phut, giay, daQua }.
 */
function tinhKhoangCach() {
    const hienTai = Date.now();
    const khoangCach = NGAY_TET - hienTai;

    if (khoangCach <= 0) {
        return { daQua: true };
    }

    const ngay = Math.floor(khoangCach / (1000 * 60 * 60 * 24));
    const gio = Math.floor((khoangCach % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const phut = Math.floor((khoangCach % (1000 * 60 * 60)) / (1000 * 60));
    const giay = Math.floor((khoangCach % (1000 * 60)) / 1000);

    return { ngay, gio, phut, giay, daQua: false };
}

/**
 * Render đồng hồ vào khung #dong-ho.
 * Dùng createElement + textContent — KHÔNG dùng innerHTML.
 */
function capNhatDongHo() {
    if (!khungDongHo) return;

    const kq = tinhKhoangCach();
    khungDongHo.textContent = "";

    if (kq.daQua) {
        const p = document.createElement("p");
        p.className = "dong-ho-tet__ket-thuc";
        p.textContent = "🎉 Chúc Mừng Năm Mới! Tết đã đến rồi!";
        p.setAttribute("data-vi", "🎉 Chúc Mừng Năm Mới! Tết đã đến rồi!");
        p.setAttribute("data-en", "🎉 Happy New Year! The Lunar New Year has arrived!");
        khungDongHo.appendChild(p);
        return;
    }

    const cacO = [
        { so: kq.ngay, vi: "Ngày", en: "Days" },
        { so: kq.gio, vi: "Giờ", en: "Hours" },
        { so: kq.phut, vi: "Phút", en: "Minutes" },
        { so: kq.giay, vi: "Giây", en: "Seconds" }
    ];

    cacO.forEach((o) => {
        const div = document.createElement("div");
        div.className = "dong-ho-tet__o";

        const soEl = document.createElement("span");
        soEl.className = "dong-ho-tet__so";
        soEl.textContent = String(o.so).padStart(2, "0");

        const nhanEl = document.createElement("span");
        nhanEl.className = "dong-ho-tet__nhan";
        nhanEl.textContent = o.vi;
        nhanEl.setAttribute("data-vi", o.vi);
        nhanEl.setAttribute("data-en", o.en);

        div.appendChild(soEl);
        div.appendChild(nhanEl);
        khungDongHo.appendChild(div);
    });
}

// Chạy lần đầu + cập nhật mỗi giây
if (khungDongHo) {
    capNhatDongHo();
    setInterval(capNhatDongHo, 1000);
}

// =========================================================
// TƯƠNG TÁC 2: ĐỔI NGÔN NGỮ VI/EN
// =========================================================

const KHOA_NGON_NGU = "edugpa-duy-ngon-ngu";
const nutDoiNgonNgu = document.querySelector("#nut-doi-ngon-ngu");
const nhanNgonNgu = document.querySelector("#nhan-ngon-ngu");

/**
 * Áp dụng ngôn ngữ cho toàn trang.
 * Mọi phần tử có [data-vi][data-en] sẽ được cập nhật textContent.
 */
function apDungNgonNgu(ngonNgu) {
    const thuocTinh = ngonNgu === "en" ? "data-en" : "data-vi";

    const cacPhanTu = document.querySelectorAll("[data-vi][data-en]");
    cacPhanTu.forEach((el) => {
        const noiDung = el.getAttribute(thuocTinh);
        if (noiDung) {
            el.textContent = noiDung;
        }
    });

    if (nhanNgonNgu) {
        nhanNgonNgu.textContent = ngonNgu === "en" ? "VI" : "EN";
    }

    document.documentElement.lang = ngonNgu === "en" ? "en" : "vi";
}

function layNgonNguDaLuu() {
    try {
        return localStorage.getItem(KHOA_NGON_NGU) || "vi";
    } catch (loi) {
        console.error("Không đọc được localStorage:", loi);
        return "vi";
    }
}

function luuNgonNgu(ngonNgu) {
    try {
        localStorage.setItem(KHOA_NGON_NGU, ngonNgu);
    } catch (loi) {
        console.error("Không ghi được localStorage:", loi);
    }
}

if (nutDoiNgonNgu) {
    // Khôi phục ngôn ngữ khi tải trang
    apDungNgonNgu(layNgonNguDaLuu());

    nutDoiNgonNgu.addEventListener("click", () => {
        const hienTai = document.documentElement.lang === "en" ? "en" : "vi";
        const moi = hienTai === "vi" ? "en" : "vi";
        apDungNgonNgu(moi);
        luuNgonNgu(moi);
    });
}