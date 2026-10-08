/**
 * trang-chi-tiet.js
 * Đọc id môn học từ URL và tải dữ liệu từ data/mon-hoc.json.
 * Hiển thị chi tiết môn học an toàn bằng createElement và textContent.
 * Hỗ trợ trạng thái đang tải, không tìm thấy và lỗi tải dữ liệu.
 */

import { taiJSON } from "./api.js";

const khung = document.querySelector("#chi-tiet");

const thamSo = new URLSearchParams(
    window.location.search
);

const id = Number(
    thamSo.get("id")
);

/**
 * Hiển thị trạng thái trong vùng chi tiết.
 */
function hienThiTrangThai(noiDung) {
    if (!khung) {
        return;
    }

    khung.replaceChildren();

    const thongBao = document.createElement("p");

    thongBao.className = "trang-thai";
    thongBao.textContent = noiDung;

    khung.append(thongBao);
}

/**
 * Tạo một mục thông tin môn học.
 */
function taoMucThongTin(nhan, giaTri) {
    const muc = document.createElement("li");

    const tenThongTin =
        document.createElement("strong");

    tenThongTin.textContent = `${nhan}: `;

    const noiDung = document.createTextNode(
        String(giaTri)
    );

    muc.append(
        tenThongTin,
        noiDung
    );

    return muc;
}

/**
 * Tạo nút yêu thích.
 * main.js sẽ xử lý sự kiện bằng event delegation.
 */
function taoNutYeuThich(mon) {
    const nutYeuThich =
        document.createElement("button");

    nutYeuThich.type = "button";
    nutYeuThich.className = "nut-yeu-thich";

    nutYeuThich.dataset.yeuThichId =
        String(mon.id);

    nutYeuThich.setAttribute(
        "aria-pressed",
        "false"
    );

    const nhanNut =
        document.createElement("span");

    nhanNut.dataset.nhanYeuThich = "";
    nhanNut.textContent = "Yêu thích";

    nutYeuThich.append(nhanNut);

    return nutYeuThich;
}

/**
 * Tạo form POST gửi đến gio-hang.php để thêm môn học vào kế hoạch học tập.
 */
function taoFormThemGioHang(mon) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "gio-hang.php";
    form.className = "form-them-gio-hang";

    // Các input hidden: hanh_dong, id, so_luong
    const inputHanhDong = document.createElement("input");
    inputHanhDong.type = "hidden";
    inputHanhDong.name = "hanh_dong";
    inputHanhDong.value = "them";

    const inputId = document.createElement("input");
    inputId.type = "hidden";
    inputId.name = "id";
    inputId.value = String(mon.id);

    const inputSoLuong = document.createElement("input");
    inputSoLuong.type = "hidden";
    inputSoLuong.name = "so_luong";
    inputSoLuong.value = "1";

    // Nút bấm submit
    const nutSubmit = document.createElement("button");
    nutSubmit.type = "submit";
    nutSubmit.className = "nut-them-ke-hoach-ct";
    nutSubmit.textContent = " Thêm vào kế hoạch học tập";

    form.append(inputHanhDong, inputId, inputSoLuong, nutSubmit);

    return form;
}

/**
 * Hiển thị đầy đủ thông tin một môn học.
 */
function hienThiChiTiet(mon) {
    if (!khung) {
        return;
    }

    khung.replaceChildren();

    const tieuDe = document.createElement("h2");
    tieuDe.textContent = mon.ten;

    const hinh = document.createElement("img");

    hinh.src = mon.anh;
    hinh.alt = `Minh họa môn học ${mon.ten}`;
    hinh.width = 400;
    hinh.height = 250;
    hinh.decoding = "async";

    const danhSachThongTin =
        document.createElement("ul");

    danhSachThongTin.className =
        "thong-tin-mon";

    const thongTin = [
        ["Mã môn", mon.maMon],
        ["Số tín chỉ", mon.soTinChi],
        ["Trạng thái", mon.trangThai]
    ];

    thongTin.forEach(([nhan, giaTri]) => {
        danhSachThongTin.append(
            taoMucThongTin(
                nhan,
                giaTri
            )
        );
    });

    const moTa = document.createElement("p");

    moTa.className = "chi-tiet__mo-ta";

    if (
        typeof mon.moTa === "string" &&
        mon.moTa.trim() !== ""
    ) {
        moTa.textContent = mon.moTa;
    } else {
        moTa.textContent =
            `${mon.ten} là môn học có ` +
            `${mon.soTinChi} tín chỉ. ` +
            `Trạng thái hiện tại: ` +
            `${mon.trangThai}.`;
    }

    const vungHanhDong =
        document.createElement("div");

    vungHanhDong.className =
        "chi-tiet__hanh-dong";

    const nutQuayLai =
        document.createElement("a");

    nutQuayLai.href = "danh-sach.php";
    nutQuayLai.className = "nut-quay-lai";
    nutQuayLai.textContent =
        "← Quay lại danh sách";

    const nutYeuThich =
        taoNutYeuThich(mon);

    // Tạo form thêm vào kế hoạch học tập
    const formThemGioHang = taoFormThemGioHang(mon);

    vungHanhDong.append(
        nutQuayLai,
        nutYeuThich,
        formThemGioHang
    );

    khung.append(
        tieuDe,
        hinh,
        danhSachThongTin,
        moTa,
        vungHanhDong
    );
}

/**
 * Hiển thị trạng thái khi URL không có id.
 */
function hienThiThieuId() {
    hienThiTrangThai(
        "Vui lòng chọn một môn học từ trang danh sách."
    );

    if (!khung) {
        return;
    }

    const lienKet =
        document.createElement("a");

    lienKet.href = "danh-sach.php";
    lienKet.className = "nut-quay-lai";
    lienKet.textContent =
        "Xem danh sách môn học";

    khung.append(lienKet);
}

/**
 * Hiển thị trạng thái không tìm thấy môn học.
 */
function hienThiKhongTimThay() {
    hienThiTrangThai(
        `Không tìm thấy môn học có id = ${id}.`
    );

    if (!khung) {
        return;
    }

    const lienKet =
        document.createElement("a");

    lienKet.href = "danh-sach.php";
    lienKet.className = "nut-quay-lai";
    lienKet.textContent =
        "Quay lại danh sách môn học";

    khung.append(lienKet);
}

/**
 * Tải dữ liệu và tìm môn học theo id.
 */
async function taiChiTietMonHoc() {
    if (!khung) {
        return;
    }

    if (
        !Number.isInteger(id) ||
        id <= 0
    ) {
        hienThiThieuId();
        return;
    }

    hienThiTrangThai(
        "Đang tải dữ liệu môn học..."
    );

    try {
        const danhSach = await taiJSON(
            "data/mon-hoc.json"
        );

        if (!Array.isArray(danhSach)) {
            throw new Error(
                "Dữ liệu môn học không hợp lệ."
            );
        }

        const mon = danhSach.find(
            (muc) => Number(muc.id) === id
        );

        if (!mon) {
            hienThiKhongTimThay();
            return;
        }

        document.title =
            `${mon.ten} | EduGPA`;

        hienThiChiTiet(mon);
    } catch (loi) {
        console.error(
            "Lỗi tải chi tiết môn học:",
            loi
        );

        hienThiTrangThai(
            "Không tải được dữ liệu môn học. " +
            "Vui lòng kiểm tra kết nối và thử lại."
        );
    }
}

taiChiTietMonHoc();