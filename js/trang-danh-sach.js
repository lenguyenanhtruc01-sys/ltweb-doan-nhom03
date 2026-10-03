/*
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
 * Tạo một dòng thông tin an toàn, không dùng innerHTML.
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
 * Tạo nút yêu thích cho một môn học.
 * Sự kiện nhấn nút được xử lý bằng event delegation trong main.js.
 */
function taoNutYeuThich(mon) {
    const nutYeuThich = document.createElement("button");

    nutYeuThich.type = "button";
    nutYeuThich.className = "nut-yeu-thich";
    nutYeuThich.dataset.yeuThichId = String(mon.id);
    nutYeuThich.setAttribute("aria-pressed", "false");

    const nhanYeuThich = document.createElement("span");
    nhanYeuThich.dataset.nhanYeuThich = "";
    nhanYeuThich.textContent = "Thêm vào yêu thích";

    nutYeuThich.append(nhanYeuThich);

    return nutYeuThich;
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

    const maMon = taoDongThongTin(
        "Mã môn",
        mon.maMon
    );

    const soTinChi = taoDongThongTin(
        "Số tín chỉ",
        mon.soTinChi
    );

    const trangThai = taoDongThongTin(
        "Trạng thái",
        mon.trangThai
    );

    const vungHanhDong = document.createElement("div");
    vungHanhDong.className = "the-san-pham__hanh-dong";

    const nutChiTiet = document.createElement("a");
    nutChiTiet.href = `chi-tiet.html?id=${mon.id}`;
    nutChiTiet.className = "the-san-pham__lien-ket";
    nutChiTiet.textContent = "Xem chi tiết →";

    const nutYeuThich = taoNutYeuThich(mon);

    vungHanhDong.append(
        nutChiTiet,
        nutYeuThich
    );

    phanThan.append(
        tieuDe,
        maMon,
        soTinChi,
        trangThai,
        vungHanhDong
    );

    theBai.append(
        anh,
        phanThan
    );

    return theBai;
}

/**
 * Hiển thị danh sách môn học ra giao diện.
 */
function hienThiDanhSach(danhSach) {
    if (!vungChua || !vungThongBao) {
        return;
    }

    vungChua.replaceChildren();

    if (danhSach.length === 0) {
        const thongBaoTrong = document.createElement("p");

        thongBaoTrong.className = "thong-bao-trong";
        thongBaoTrong.textContent =
            "Không tìm thấy môn học nào phù hợp.";

        vungChua.append(thongBaoTrong);

        vungThongBao.textContent =
            "Không có kết quả phù hợp.";

        return;
    }

    const fragment = document.createDocumentFragment();

    danhSach.forEach((mon) => {
        fragment.append(taoTheMonHoc(mon));
    });

    vungChua.append(fragment);

    vungThongBao.textContent =
        `Đang hiển thị ${danhSach.length} môn học.`;
}

/**
 * Tìm kiếm, lọc và sắp xếp dữ liệu.
 */
function xuLyDuLieu() {
    if (
        !oTimKiem ||
        !locTrangThai ||
        !sapXep
    ) {
        return;
    }

    const tuKhoa = xoaDau(
        oTimKiem.value.trim()
    );

    const giaTriLoc = locTrangThai.value;
    const giaTriSapXep = sapXep.value;

    let ketQua = duLieuGoc.filter((mon) => {
        const tenMon = xoaDau(mon.ten);
        const maMon = xoaDau(mon.maMon);

        const thoaTuKhoa =
            tenMon.includes(tuKhoa) ||
            maMon.includes(tuKhoa);

        const thoaLoc =
            giaTriLoc === "tat-ca" ||
            mon.trangThai === giaTriLoc;

        return thoaTuKhoa && thoaLoc;
    });

    /*
     * Tạo mảng mới trước khi sort
     * để không làm thay đổi dữ liệu gốc.
     */
    ketQua = [...ketQua];

    if (giaTriSapXep === "ten-az") {
        ketQua.sort((a, b) =>
            a.ten.localeCompare(b.ten, "vi")
        );
    } else if (giaTriSapXep === "ten-za") {
        ketQua.sort((a, b) =>
            b.ten.localeCompare(a.ten, "vi")
        );
    } else if (giaTriSapXep === "tin-chi-giam") {
        ketQua.sort(
            (a, b) => b.soTinChi - a.soTinChi
        );
    } else if (giaTriSapXep === "tin-chi-tang") {
        ketQua.sort(
            (a, b) => a.soTinChi - b.soTinChi
        );
    }

    hienThiDanhSach(ketQua);
}

/**
 * Hiển thị trạng thái lỗi và nút thử lại.
 */
function hienThiLoi() {
    if (!vungChua || !vungThongBao) {
        return;
    }

    vungChua.replaceChildren();

    const thongBaoLoi = document.createElement("p");
    thongBaoLoi.className = "thong-bao-loi";
    thongBaoLoi.textContent =
        "Không thể tải dữ liệu môn học.";

    const nutThuLai = document.createElement("button");
    nutThuLai.type = "button";
    nutThuLai.className = "nut nut--chinh";
    nutThuLai.textContent = "Thử lại";

    nutThuLai.addEventListener(
        "click",
        taiDanhSachMonHoc
    );

    vungChua.append(
        thongBaoLoi,
        nutThuLai
    );

    vungThongBao.textContent =
        "Lỗi: Không tải được dữ liệu.";
}

/**
 * Tải dữ liệu môn học từ JSON.
 */
async function taiDanhSachMonHoc() {
    if (!vungChua || !vungThongBao) {
        return;
    }

    vungChua.textContent =
        "Đang tải dữ liệu môn học...";

    vungThongBao.textContent =
        "Đang tải dữ liệu...";

    try {
        duLieuGoc = await taiJSON(
            "data/mon-hoc.json"
        );

        if (!Array.isArray(duLieuGoc)) {
            throw new Error(
                "Dữ liệu môn học không hợp lệ."
            );
        }

        xuLyDuLieu();
    } catch (loi) {
        console.error(
            "Lỗi tải dữ liệu môn học:",
            loi
        );

        hienThiLoi();
    }
}

/**
 * Gắn các sự kiện tìm kiếm, lọc và sắp xếp.
 */
function ganSuKien() {
    oTimKiem?.addEventListener(
        "input",
        xuLyDuLieu
    );

    locTrangThai?.addEventListener(
        "change",
        xuLyDuLieu
    );

    sapXep?.addEventListener(
        "change",
        xuLyDuLieu
    );
}

/**
 * Khởi tạo trang.
 */
function khoiTao() {
    ganSuKien();
    taiDanhSachMonHoc();
}

khoiTao();
