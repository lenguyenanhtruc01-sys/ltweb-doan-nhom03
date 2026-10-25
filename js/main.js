/**
 * main.js quản lý menu điện thoại và danh sách môn học yêu thích.
 * Menu hỗ trợ chuột, bàn phím, phím Escape và aria-expanded.
 * Khách lưu localStorage; tài khoản lưu yêu thích qua API MySQL riêng.
 * Số lượng yêu thích được cập nhật trên header của năm trang chính.
 * Các nút yêu thích được xử lý bằng event delegation.
 */

document.documentElement.classList.add("js");

/* =========================================================
   1. MENU ĐIỆN THOẠI
   ========================================================= */

const nutMenu = document.querySelector(".nut-menu");
const danhSachMenu = document.querySelector("#menu-chinh");

if (nutMenu !== null && danhSachMenu !== null) {
    const menuDangMo = () =>
        nutMenu.getAttribute("aria-expanded") === "true";

    const datTrangThaiMenu = (moMenu) => {
        nutMenu.setAttribute(
            "aria-expanded",
            String(moMenu)
        );

        nutMenu.setAttribute(
            "aria-label",
            moMenu
                ? "Đóng menu chính"
                : "Mở menu chính"
        );

        danhSachMenu.classList.toggle(
            "menu-chinh--mo",
            moMenu
        );
    };

    nutMenu.addEventListener("click", () => {
        datTrangThaiMenu(!menuDangMo());
    });

    document.addEventListener("keydown", (suKien) => {
        if (
            suKien.key === "Escape" &&
            menuDangMo()
        ) {
            datTrangThaiMenu(false);
            nutMenu.focus();
        }
    });

    danhSachMenu.addEventListener(
        "click",
        (suKien) => {
            if (
                suKien.target instanceof Element &&
                suKien.target.closest("a") !== null
            ) {
                datTrangThaiMenu(false);
            }
        }
    );

    const manHinhLon = window.matchMedia(
        "(min-width: 768px)"
    );

    manHinhLon.addEventListener(
        "change",
        (suKien) => {
            if (suKien.matches) {
                datTrangThaiMenu(false);
            }
        }
    );
}

/* =========================================================
   2. DANH SÁCH MÔN HỌC YÊU THÍCH
   ========================================================= */

const KHOA_YEU_THICH =
    "edugpa-mon-hoc-yeu-thich";
const uidYeuThich = document.querySelector('meta[name="edugpa-uid"]')?.content ?? '';
const apiYeuThich = document.querySelector('meta[name="yeu-thich-api"]')?.content ?? '';
const dungTaiKhoan = /^[1-9][0-9]*$/.test(uidYeuThich);
let yeuThichTaiKhoan = [];
let daTaiYeuThich = !dungTaiKhoan;
let loiDongBoYeuThich = false;

async function goiApiYeuThich(data) {
    const response = await fetch(apiYeuThich, {
        method: data ? 'POST' : 'GET', credentials: 'same-origin',
        headers: {Accept: 'application/json', ...(data ? {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-gio-hang"]')?.content ?? '',
        } : {})},
        ...(data ? {body: JSON.stringify(data)} : {}),
    });
    const result = await response.json();
    if (!response.ok || !Array.isArray(result.du_lieu)) {
        throw new Error(result.loi || 'Không lưu được yêu thích. Vui lòng thử lại.');
    }
    yeuThichTaiKhoan = result.du_lieu;
    loiDongBoYeuThich = false;
}

function baoLoiYeuThich(loi) {
    loiDongBoYeuThich = true;
    let status = document.getElementById('yeu-thich-status');
    if (!status) {
        status = document.createElement('p'); status.id = 'yeu-thich-status';
        status.setAttribute('role', 'alert');
        document.querySelector('main')?.prepend(status);
    }
    status.textContent = loi.message || 'Không lưu được yêu thích.';
}

let hangDoiYeuThich = dungTaiKhoan ? (async () => {
    try {
        await goiApiYeuThich();
        // Chuyển danh sách trình duyệt cũ vào tài khoản hiện tại đúng một lần.
        let legacy = [];
        try { legacy = JSON.parse(localStorage.getItem(KHOA_YEU_THICH) || '[]'); } catch {}
        if (Array.isArray(legacy)) {
            for (const id of new Set(legacy.map(Number).filter(id => Number.isInteger(id) && id > 0))) {
                await goiApiYeuThich({id, chon: true});
            }
            try { localStorage.removeItem(KHOA_YEU_THICH); } catch {}
        }
        daTaiYeuThich = true;
        capNhatGiaoDienYeuThich();
    } catch (loi) { baoLoiYeuThich(loi); }
})() : Promise.resolve();

function docDanhSachYeuThich() {
    if (dungTaiKhoan) return yeuThichTaiKhoan;
    try {
        const duLieuDaLuu =
            localStorage.getItem(KHOA_YEU_THICH);

        if (duLieuDaLuu === null) {
            return [];
        }

        const danhSach = JSON.parse(duLieuDaLuu);

        if (!Array.isArray(danhSach)) {
            return [];
        }

        return [
            ...new Set(
                danhSach
                    .map((id) => Number(id))
                    .filter(
                        (id) =>
                            Number.isInteger(id) &&
                            id > 0
                    )
            )
        ];
    } catch (loi) {
        console.warn(
            "Không đọc được danh sách yêu thích:",
            loi
        );

        return [];
    }
}

function luuDanhSachYeuThich(danhSach) {
    try {
        localStorage.setItem(
            KHOA_YEU_THICH,
            JSON.stringify(danhSach)
        );

        return true;
    } catch (loi) {
        console.warn(
            "Không lưu được danh sách yêu thích:",
            loi
        );

        return false;
    }
}

function capNhatGiaoDienYeuThich() {
    const danhSachYeuThich =
        docDanhSachYeuThich();

    const tapYeuThich =
        new Set(danhSachYeuThich);

    document
        .querySelectorAll(
            "[data-so-luong-yeu-thich]"
        )
        .forEach((phanTu) => {
            phanTu.textContent =
                String(danhSachYeuThich.length);
        });

    document
        .querySelectorAll(
            "button[data-yeu-thich-id]"
        )
        .forEach((nut) => {
            const id = Number(
                nut.dataset.yeuThichId
            );

            const daYeuThich =
                tapYeuThich.has(id);

            const noiDungNut =
                nut.querySelector(
                    "[data-nhan-yeu-thich]"
                );

            const nhanNut = daYeuThich
                ? "Xóa khỏi yêu thích"
                : "Thêm vào yêu thích";

            nut.setAttribute(
                "aria-pressed",
                String(daYeuThich)
            );

            nut.classList.toggle(
                "nut-yeu-thich--da-chon",
                daYeuThich
            );

            if (noiDungNut !== null) {
                noiDungNut.textContent =
                    nhanNut;
            }
        });
}

async function doiTrangThaiYeuThich(id) {
    if (dungTaiKhoan) {
        if (!daTaiYeuThich) {
            await goiApiYeuThich();
            daTaiYeuThich = true;
        }
        await goiApiYeuThich({id, chon: !yeuThichTaiKhoan.includes(id)});
        capNhatGiaoDienYeuThich();
        return;
    }
    const danhSachYeuThich =
        docDanhSachYeuThich();

    const tapYeuThich =
        new Set(danhSachYeuThich);

    if (tapYeuThich.has(id)) {
        tapYeuThich.delete(id);
    } else {
        tapYeuThich.add(id);
    }

    const danhSachMoi = [...tapYeuThich];

    if (luuDanhSachYeuThich(danhSachMoi)) {
        capNhatGiaoDienYeuThich();
    }
}

/* =========================================================
   3. EVENT DELEGATION CHO NÚT YÊU THÍCH
   ========================================================= */

document.addEventListener(
    "click",
    (suKien) => {
        if (
            !(suKien.target instanceof Element)
        ) {
            return;
        }

        const nutYeuThich =
            suKien.target.closest(
                "button[data-yeu-thich-id]"
            );

        if (nutYeuThich === null) {
            return;
        }

        const id = Number(
            nutYeuThich.dataset.yeuThichId
        );

        if (
            !Number.isInteger(id) ||
            id <= 0
        ) {
            return;
        }

        hangDoiYeuThich = hangDoiYeuThich.then(() => doiTrangThaiYeuThich(id)).catch(baoLoiYeuThich);
    }
);

/* =========================================================
   4. THEO DÕI NÚT YÊU THÍCH TẠO ĐỘNG
   ========================================================= */

const boQuanSatYeuThich =
    new MutationObserver((cacThayDoi) => {
        const coNutMoi = cacThayDoi.some(
            (thayDoi) =>
                [...thayDoi.addedNodes].some(
                    (nutMoi) =>
                        nutMoi instanceof Element &&
                        (
                            nutMoi.matches(
                                "button[data-yeu-thich-id]"
                            ) ||
                            nutMoi.querySelector(
                                "button[data-yeu-thich-id]"
                            ) !== null
                        )
                )
        );

        if (coNutMoi) {
            capNhatGiaoDienYeuThich();
        }
    });

boQuanSatYeuThich.observe(
    document.body,
    {
        childList: true,
        subtree: true
    }
);

window.addEventListener(
    "storage",
    (suKien) => {
        if (
            suKien.key === KHOA_YEU_THICH
        ) {
            capNhatGiaoDienYeuThich();
        }
    }
);

capNhatGiaoDienYeuThich();

// Chờ các lần bấm yêu thích hoàn tất trước khi chuyển sang đăng xuất.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!dungTaiKhoan || !(form instanceof HTMLFormElement)
        || new URL(form.action).pathname.split('/').pop() !== 'dang-xuat.php') return;
    event.preventDefault();
    hangDoiYeuThich.then(() => {
        if (!loiDongBoYeuThich) HTMLFormElement.prototype.submit.call(form);
        else baoLoiYeuThich(new Error('Chưa lưu được yêu thích. Vui lòng thử lại trước khi đăng xuất.'));
    });
});
