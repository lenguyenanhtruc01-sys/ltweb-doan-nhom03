/**
 * Tệp canhan.js tạo hai tương tác cho trang cá nhân của Phương Uyên.
 * Chức năng thứ nhất thay đổi cỡ chữ và ghi nhớ bằng localStorage.
 * Chức năng thứ hai tìm kiếm, tô sáng môn học trong thời khóa biểu.
 * Cách thử: bấm các nút cỡ chữ và nhập tên hoặc mã môn vào ô tìm kiếm.
 */

const KHOA_LUU_CO_CHU = "edugpa-co-chu-uyen";
const CAC_CO_CHU = ["nho", "vua", "lon"];

const TEN_CO_CHU = {
    nho: "nhỏ",
    vua: "vừa",
    lon: "lớn"
};

const cacNutCoChu = document.querySelectorAll(".nut-co-chu");
const thongBaoCoChu = document.querySelector("#thong-bao-co-chu");

const oTimMonHoc = document.querySelector("#o-tim-mon-hoc");
const nutXoaTim = document.querySelector("#nut-xoa-tim");
const ketQuaTimMon = document.querySelector("#ket-qua-tim-mon");
const cacMonHoc = document.querySelectorAll(
    ".thoi-khoa-bieu .mon-hoc"
);

/* =========================================================
   TƯƠNG TÁC 1: THAY ĐỔI CỠ CHỮ
   ========================================================= */

function docCoChuDaLuu() {
    try {
        const coChuDaLuu = localStorage.getItem(KHOA_LUU_CO_CHU);

        if (CAC_CO_CHU.includes(coChuDaLuu)) {
            return coChuDaLuu;
        }
    } catch {
        return "vua";
    }

    return "vua";
}

function luuCoChu(coChu) {
    try {
        localStorage.setItem(KHOA_LUU_CO_CHU, coChu);
        return true;
    } catch {
        return false;
    }
}

function apDungCoChu(coChu, thongBao = true) {
    const coChuHopLe = CAC_CO_CHU.includes(coChu)
        ? coChu
        : "vua";

    CAC_CO_CHU.forEach((tenLop) => {
        document.documentElement.classList.remove(
            `co-chu-${tenLop}`
        );
    });

    document.documentElement.classList.add(
        `co-chu-${coChuHopLe}`
    );

    cacNutCoChu.forEach((nut) => {
        const dangDuocChon = nut.dataset.coChu === coChuHopLe;

        nut.setAttribute(
            "aria-pressed",
            String(dangDuocChon)
        );
    });

    if (thongBao && thongBaoCoChu !== null) {
        thongBaoCoChu.textContent =
            `Đã chuyển sang cỡ chữ ${TEN_CO_CHU[coChuHopLe]}.`;
    }
}

cacNutCoChu.forEach((nut) => {
    nut.addEventListener("click", () => {
        const coChuDuocChon = nut.dataset.coChu;

        if (!CAC_CO_CHU.includes(coChuDuocChon)) {
            return;
        }

        apDungCoChu(coChuDuocChon);

        const daLuuThanhCong = luuCoChu(coChuDuocChon);

        if (!daLuuThanhCong && thongBaoCoChu !== null) {
            thongBaoCoChu.textContent =
                "Đã đổi cỡ chữ nhưng trình duyệt không thể ghi nhớ lựa chọn.";
        }
    });
});

const coChuBanDau = docCoChuDaLuu();
apDungCoChu(coChuBanDau, false);

if (thongBaoCoChu !== null) {
    thongBaoCoChu.textContent =
        `Cỡ chữ hiện tại: ${TEN_CO_CHU[coChuBanDau]}.`;
}

/* =========================================================
   TƯƠNG TÁC 2: TÌM KIẾM MÔN HỌC
   ========================================================= */

function chuanHoaChuoi(chuoi) {
    return chuoi
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/Đ/g, "D")
        .toLowerCase()
        .trim();
}

function xoaDanhDauTimKiem() {
    cacMonHoc.forEach((monHoc) => {
        monHoc.classList.remove("mon-hoc--tim-thay");
        monHoc.classList.remove("mon-hoc--khong-khop");
    });
}

function timKiemMonHoc() {
    if (oTimMonHoc === null || ketQuaTimMon === null) {
        return;
    }

    const tuKhoa = chuanHoaChuoi(oTimMonHoc.value);

    if (tuKhoa === "") {
        xoaDanhDauTimKiem();
        ketQuaTimMon.textContent =
            "Nhập từ khóa để tìm môn học.";
        return;
    }

    let soKetQua = 0;

    cacMonHoc.forEach((monHoc) => {
        const noiDungMonHoc = chuanHoaChuoi(
            monHoc.textContent ?? ""
        );

        const phuHop = noiDungMonHoc.includes(tuKhoa);

        monHoc.classList.toggle(
            "mon-hoc--tim-thay",
            phuHop
        );

        monHoc.classList.toggle(
            "mon-hoc--khong-khop",
            !phuHop
        );

        if (phuHop) {
            soKetQua += 1;
        }
    });

    if (soKetQua === 0) {
        ketQuaTimMon.textContent =
            `Không tìm thấy môn học phù hợp với “${oTimMonHoc.value.trim()}”.`;
    } else {
        ketQuaTimMon.textContent =
            `Tìm thấy ${soKetQua} môn học phù hợp.`;
    }
}

if (oTimMonHoc !== null) {
    oTimMonHoc.addEventListener("input", timKiemMonHoc);

    oTimMonHoc.addEventListener("keydown", (suKien) => {
        if (suKien.key === "Escape") {
            oTimMonHoc.value = "";
            timKiemMonHoc();
        }
    });
}

if (nutXoaTim !== null && oTimMonHoc !== null) {
    nutXoaTim.addEventListener("click", () => {
        oTimMonHoc.value = "";
        timKiemMonHoc();
        oTimMonHoc.focus();
    });
}