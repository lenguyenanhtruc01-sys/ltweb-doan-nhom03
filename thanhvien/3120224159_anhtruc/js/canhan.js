/**
 * canhan.js - Trang cá nhân Lê Nguyễn Anh Trúc.
 * Chức năng 1: chuyển giao diện sáng/tối và ghi nhớ bằng localStorage.
 * Chức năng 2: sao chép email và hiển thị thông báo.
 * Cách thử: bấm hai nút tương ứng trên trang cá nhân.
 */

const nutGiaoDien = document.querySelector("#doi-giao-dien");
const nutSaoChepEmail = document.querySelector("#sao-chep-email");
const thongBao = document.querySelector("#thong-bao-ca-nhan");

const KHOA_GIAO_DIEN = "anhTrucCheDoToi";

function capNhatNutGiaoDien() {
    if (!nutGiaoDien) {
        return;
    }

    const dangToi =
        document.body.classList.contains("che-do-toi");

    nutGiaoDien.textContent = dangToi
        ? "Chuyển sang giao diện sáng"
        : "Chuyển sang giao diện tối";

    nutGiaoDien.setAttribute(
        "aria-pressed",
        String(dangToi)
    );
}

function taiGiaoDienDaLuu() {
    const dangToi =
        localStorage.getItem(KHOA_GIAO_DIEN) === "true";

    document.body.classList.toggle(
        "che-do-toi",
        dangToi
    );

    capNhatNutGiaoDien();
}

nutGiaoDien?.addEventListener("click", () => {
    const dangToi =
        document.body.classList.toggle("che-do-toi");

    localStorage.setItem(
        KHOA_GIAO_DIEN,
        String(dangToi)
    );

    capNhatNutGiaoDien();

    if (thongBao) {
        thongBao.textContent = dangToi
            ? "Đã chuyển sang giao diện tối."
            : "Đã chuyển sang giao diện sáng.";
    }
});

nutSaoChepEmail?.addEventListener("click", async () => {
    const email = nutSaoChepEmail.dataset.email;

    if (!email || !thongBao) {
        return;
    }

    try {
        await navigator.clipboard.writeText(email);

        thongBao.textContent =
            "Đã sao chép email vào bộ nhớ tạm.";
    } catch (loi) {
        console.error("Không thể sao chép email:", loi);

        thongBao.textContent =
            "Không thể sao chép email. Vui lòng thử lại.";
    }
});

taiGiaoDienDaLuu();
