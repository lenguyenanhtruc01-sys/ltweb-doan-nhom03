/**
 * trang-chu.js
 * Lấy dữ liệu thời tiết hiện tại của Đà Nẵng từ Open-Meteo.
 * Hiển thị trạng thái đang tải, thành công, rỗng và lỗi.
 */

import { taiJSON } from "./api.js";

const khungThoiTiet = document.querySelector("#thoi-tiet");

async function taiThoiTiet() {
    if (!khungThoiTiet) {
        return;
    }

    khungThoiTiet.textContent = "Đang tải dữ liệu thời tiết...";

    const thamSo = new URLSearchParams({
        latitude: "16.0544",
        longitude: "108.2022",
        current: "temperature_2m,relative_humidity_2m,wind_speed_10m",
        timezone: "Asia/Ho_Chi_Minh"
    });

    try {
        const duLieu = await taiJSON(
            `https://api.open-meteo.com/v1/forecast?${thamSo}`
        );

        if (!duLieu.current) {
            khungThoiTiet.textContent =
                "Hiện chưa có dữ liệu thời tiết.";
            return;
        }

        khungThoiTiet.replaceChildren();

        const nhietDo = document.createElement("p");
        nhietDo.textContent =
            `Nhiệt độ: ${duLieu.current.temperature_2m} °C`;

        const doAm = document.createElement("p");
        doAm.textContent =
            `Độ ẩm: ${duLieu.current.relative_humidity_2m}%`;

        const gio = document.createElement("p");
        gio.textContent =
            `Tốc độ gió: ${duLieu.current.wind_speed_10m} km/h`;

        khungThoiTiet.append(
            nhietDo,
            doAm,
            gio
        );
    } catch (loi) {
        console.error(loi);

        khungThoiTiet.textContent =
            "Không tải được dữ liệu thời tiết.";
    }
}

taiThoiTiet();
