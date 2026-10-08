<<<<<<< HEAD
/.
\
?



**
 * main.js quản lý các chức năng dùng chung trên năm trang chính.
 * Tệp xử lý việc mở và đóng menu trên điện thoại.
 * Menu hỗ trợ chuột, bàn phím và cập nhật thuộc tính aria-expanded.
 * Khi JavaScript bị tắt, CSS mặc định vẫn hiển thị toàn bộ menu.
=======
/**
 * main.js quản lý menu điện thoại và danh sách môn học yêu thích.
 * Menu hỗ trợ chuột, bàn phím, phím Escape và aria-expanded.
 * Môn yêu thích được lưu bằng JSON trong localStorage.
 * Số lượng yêu thích được cập nhật trên header của năm trang chính.
 * Các nút yêu thích được xử lý bằng event delegation.
>>>>>>> e4f884c5f2a09736dfe7dce2153772f1d6c89f32
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


function docDanhSachYeuThich() {
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

/**
 * Lưu danh sách ID môn học yêu thích.
 */
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

/**
 * Cập nhật số đếm trên header và trạng thái nút.
 */
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
            } else {
                nut.textContent =
                    nhanNut;
            }
        });
}

/**
 * Thêm hoặc xóa một môn học yêu thích.
 */
function doiTrangThaiYeuThich(id) {
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

        doiTrangThaiYeuThich(id);
    }
);

/* =========================================================
   4. ĐỒNG BỘ CÁC NÚT ĐƯỢC TẠO ĐỘNG TỪ JSON
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

/* Đồng bộ khi localStorage thay đổi ở tab khác. */
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

/* Hiển thị số lượng ngay khi trang tải xong. */
capNhatGiaoDienYeuThich();
