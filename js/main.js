/**
 * main.js quản lý các chức năng dùng chung trên năm trang chính.
 * Tệp xử lý việc mở và đóng menu trên điện thoại.
 * Menu hỗ trợ chuột, bàn phím và cập nhật thuộc tính aria-expanded.
 * Khi JavaScript bị tắt, CSS mặc định vẫn hiển thị toàn bộ menu.
 */

document.documentElement.classList.add("js");

const nutMenu = document.querySelector(".nut-menu");
const danhSachMenu = document.querySelector("#menu-chinh");

if (nutMenu !== null && danhSachMenu !== null) {
    const menuDangMo = () =>
        nutMenu.getAttribute("aria-expanded") === "true";

    const datTrangThaiMenu = (moMenu) => {
        nutMenu.setAttribute("aria-expanded", String(moMenu));

        nutMenu.setAttribute(
            "aria-label",
            moMenu ? "Đóng menu chính" : "Mở menu chính"
        );

        danhSachMenu.classList.toggle("menu-chinh--mo", moMenu);
    };

    nutMenu.addEventListener("click", () => {
        datTrangThaiMenu(!menuDangMo());
    });

    document.addEventListener("keydown", (suKien) => {
        if (suKien.key === "Escape" && menuDangMo()) {
            datTrangThaiMenu(false);
            nutMenu.focus();
        }
    });

    danhSachMenu.addEventListener("click", (suKien) => {
        if (
            suKien.target instanceof Element &&
            suKien.target.closest("a") !== null
        ) {
            datTrangThaiMenu(false);
        }
    });

    const manHinhLon = window.matchMedia("(min-width: 768px)");

    manHinhLon.addEventListener("change", (suKien) => {
        if (suKien.matches) {
            datTrangThaiMenu(false);
        }
    });
}