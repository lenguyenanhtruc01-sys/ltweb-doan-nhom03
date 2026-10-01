/**
 * trang-danh-sach.js
 * Tải dữ liệu môn học từ tệp JSON.
 * Hỗ trợ tìm kiếm không dấu, lọc trạng thái và sắp xếp.
 * Hiển thị dữ liệu an toàn bằng textContent và createElement.
 */

import { taiJSON } from "./api.js";

const vungChua = document.querySelector(".luoi-san-pham");
const oTimKiem = document.querySelector("#tim-kiem");
const vungThongBao = document.querySelector("#thong-bao-ket-qua");
const locTrangThai = document.querySelector("#loc-trang-thai");
const sapXep = document.querySelector("#sap-xep");

let duLieuGoc = [];

// ====================================================
// CÁC HÀM XỬ LÝ YÊU THÍCH (LOCALSTORAGE)
// ====================================================
function docYeuThich() {
    const duLieu = localStorage.getItem("danhSachYeuThich");
    return duLieu ? JSON.parse(duLieu) : [];
}

function luuYeuThich(mang) {
    localStorage.setItem("danhSachYeuThich", JSON.stringify(mang));
}

function capNhatSoDemYeuThich() {
    // Cập nhật số đếm trên thanh Header
    const soDemHienThi = document.querySelector("#so-dem-yeu-thich");
    if (soDemHienThi) {
        const mang = docYeuThich();
        soDemHienThi.textContent = mang.length;
    }
}
// ====================================================

/**
 * Xóa dấu tiếng Việt để hỗ trợ tìm kiếm không dấu.
 */
function xoaDau(chuoi) {
    return String(chuoi)
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/Đ/g, "D")
        .toLowerCase();
}

/**
 * Tạo một dòng thông tin theo cách an toàn.
 */
function taoDongThongTin(nhan, giaTri) {
    const dong = document.createElement("p");
    const tieuDe = document.createElement("strong");
    tieuDe.textContent = `${nhan}: `;
    const noiDung = document.createTextNode(String(giaTri));
    dong.append(tieuDe, noiDung);
    return dong;
}

/**
 * Tạo một thẻ môn học.
 */
function taoTheMonHoc(mon) {
    const theBai = document.createElement("article");
    theBai.className = "the-san-pham";

    const anh = document.createElement("img");
    anh.src = mon.anh;
    anh.alt = `Minh họa môn học ${mon.ten}`;
    anh.width = 400;
    anh.height = 250;
    anh.loading = "lazy";

    const phanThan = document.createElement("div");
    phanThan.className = "the-san-pham__than";

    const tieuDe = document.createElement("h3");
    tieuDe.textContent = mon.ten;

    const maMon = taoDongThongTin("Mã môn", mon.maMon);
    const soTinChi = taoDongThongTin("Số tín chỉ", mon.soTinChi);
    const trangThai = taoDongThongTin("Trạng thái", mon.trangThai);

    const nutChiTiet = document.createElement("a");
    nutChiTiet.href = `chi-tiet.html?id=${mon.id}`;
    nutChiTiet.className = "the-san-pham__lien-ket";
    nutChiTiet.textContent = "Xem chi tiết →";

    // --- TẠO NÚT YÊU THÍCH ---
    const nutYeuThich = document.createElement("button");
    nutYeuThich.type = "button";
    nutYeuThich.className = "nut-yeu-thich";
    nutYeuThich.dataset.id = mon.id; // Gắn ID môn học vào thuộc tính data-id
    nutYeuThich.style.cssText = "margin-top: 10px; padding: 6px 12px; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; background: #fff; font-weight: bold; width: 100%; transition: 0.2s;";

    // Kiểm tra trạng thái hiện tại trong localStorage
    const danhSachYeuThich = docYeuThich();
    const daThich = danhSachYeuThich.includes(String(mon.id));
    
    nutYeuThich.textContent = daThich ? "❤️ Đã thích" : "🤍 Yêu thích";
    if (daThich) {
        nutYeuThich.style.borderColor = "#ffc72c";
        nutYeuThich.style.backgroundColor = "#fff9e6";
        nutYeuThich.style.color = "#d9534f";
    }

    phanThan.append(
        tieuDe,
        maMon,
        soTinChi,
        trangThai,
        nutChiTiet,
        nutYeuThich // Thêm nút vào giao diện
    );

    theBai.append(anh, phanThan);
    return theBai;
}

/**
 * Hiển thị danh sách môn học ra giao diện.
 */
function hienThiDanhSach(danhSach) {
    if (!vungChua || !vungThongBao) return;

    vungChua.replaceChildren();

    if (danhSach.length === 0) {
        const thongBaoTrong = document.createElement("p");
        thongBaoTrong.className = "thong-bao-trong";
        thongBaoTrong.textContent = "Không tìm thấy môn học nào phù hợp.";
        vungChua.append(thongBaoTrong);
        vungThongBao.textContent = "Không có kết quả phù hợp.";
        return;
    }

    const fragment = document.createDocumentFragment();
    danhSach.forEach((mon) => {
        fragment.append(taoTheMonHoc(mon));
    });

    vungChua.append(fragment);
    vungThongBao.textContent = `Đang hiển thị ${danhSach.length} môn học.`;
}

/**
 * Tìm kiếm, lọc và sắp xếp dữ liệu.
 */
function xuLyDuLieu() {
    if (!oTimKiem || !locTrangThai || !sapXep) return;

    const tuKhoa = xoaDau(oTimKiem.value.trim());
    const giaTriLoc = locTrangThai.value;
    const giaTriSapXep = sapXep.value;

    let ketQua = duLieuGoc.filter((mon) => {
        const tenMon = xoaDau(mon.ten);
        const maMon = xoaDau(mon.maMon);

        const thoaTuKhoa = tenMon.includes(tuKhoa) || maMon.includes(tuKhoa);
        const thoaLoc = giaTriLoc === "tat-ca" || mon.trangThai === giaTriLoc;

        return thoaTuKhoa && thoaLoc;
    });

    ketQua = [...ketQua];

    if (giaTriSapXep === "ten-az") {
        ketQua.sort((a, b) => a.ten.localeCompare(b.ten, "vi"));
    } else if (giaTriSapXep === "ten-za") {
        ketQua.sort((a, b) => b.ten.localeCompare(a.ten, "vi"));
    } else if (giaTriSapXep === "tin-chi-giam") {
        ketQua.sort((a, b) => b.soTinChi - a.soTinChi);
    } else if (giaTriSapXep === "tin-chi-tang") {
        ketQua.sort((a, b) => a.soTinChi - b.soTinChi);
    }

    hienThiDanhSach(ketQua);
}

/**
 * Hiển thị trạng thái lỗi và nút thử lại.
 */
function hienThiLoi() {
    if (!vungChua || !vungThongBao) return;

    vungChua.replaceChildren();

    const thongBaoLoi = document.createElement("p");
    thongBaoLoi.className = "thong-bao-loi";
    thongBaoLoi.textContent = "Không thể tải dữ liệu môn học.";

    const nutThuLai = document.createElement("button");
    nutThuLai.type = "button";
    nutThuLai.className = "nut nut--chinh";
    nutThuLai.textContent = "Thử lại";
    nutThuLai.addEventListener("click", taiDanhSachMonHoc);

    vungChua.append(thongBaoLoi, nutThuLai);
    vungThongBao.textContent = "Lỗi: Không tải được dữ liệu.";
}

/**
 * Tải dữ liệu môn học từ JSON.
 */
async function taiDanhSachMonHoc() {
    if (!vungChua || !vungThongBao) return;

    vungChua.textContent = "Đang tải dữ liệu môn học...";
    vungThongBao.textContent = "Đang tải dữ liệu...";

    try {
        duLieuGoc = await taiJSON("data/mon-hoc.json");
        if (!Array.isArray(duLieuGoc)) {
            throw new Error("Dữ liệu môn học không hợp lệ.");
        }
        xuLyDuLieu();
    } catch (loi) {
        console.error("Lỗi tải dữ liệu môn học:", loi);
        hienThiLoi();
    }
}

/**
 * Gắn các sự kiện.
 */
function ganSuKien() {
    oTimKiem?.addEventListener("input", xuLyDuLieu);
    locTrangThai?.addEventListener("change", xuLyDuLieu);
    sapXep?.addEventListener("change", xuLyDuLieu);

    // ====================================================
    // KỸ THUẬT ỦY QUYỀN SỰ KIỆN (EVENT DELEGATION)
    // ====================================================
    vungChua?.addEventListener("click", (suKien) => {
        // Kiểm tra xem người dùng có bấm trúng nút yêu thích không
        const nut = suKien.target.closest(".nut-yeu-thich");
        if (!nut) return; // Nếu không thì bỏ qua

        const idMonHoc = nut.dataset.id;
        let danhSachYeuThich = docYeuThich();

        if (danhSachYeuThich.includes(idMonHoc)) {
            // Đã thích -> Xóa khỏi mảng
            danhSachYeuThich = danhSachYeuThich.filter(id => id !== idMonHoc);
            nut.textContent = "🤍 Yêu thích";
            nut.style.borderColor = "#ccc";
            nut.style.backgroundColor = "#fff";
            nut.style.color = "inherit";
        } else {
            // Chưa thích -> Thêm vào mảng
            danhSachYeuThich.push(idMonHoc);
            nut.textContent = "❤️️ Đã thích";
            nut.style.borderColor = "#ffc72c";
            nut.style.backgroundColor = "#fff9e6";
            nut.style.color = "#d9534f";
        }

        // Lưu lại dữ liệu và cập nhật bộ đếm
        luuYeuThich(danhSachYeuThich);
        capNhatSoDemYeuThich();
    });
}

/**
 * Khởi tạo trang.
 */
function khoiTao() {
    ganSuKien();
    capNhatSoDemYeuThich(); // Gọi lần đầu để hiện số ngay khi mới vào trang
    taiDanhSachMonHoc();
}

khoiTao();