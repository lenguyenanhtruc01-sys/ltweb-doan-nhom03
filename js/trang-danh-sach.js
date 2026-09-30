/**
 * Tệp xử lý dữ liệu động cho trang danh sách môn học
 */
import { taiJSON } from './api.js';

const vungChua = document.querySelector('.luoi-san-pham');
const oTimKiem = document.querySelector('#tim-kiem');
const vungThongBao = document.querySelector('#thong-bao-ket-qua');
const locTrangThai = document.querySelector('#loc-trang-thai');
const sapXep = document.querySelector('#sap-xep');

let duLieuGoc = [];

// Hàm xóa dấu tiếng Việt để tìm kiếm chính xác
function xoaDau(chuoi) {
    return chuoi.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

// Render dữ liệu ra giao diện an toàn bằng textContent và createElement
function hienThiDanhSach(danhSach) {
    vungChua.textContent = ''; // Xóa nội dung cũ (hoặc nội dung tĩnh dự phòng)

    if (danhSach.length === 0) {
        vungChua.textContent = 'Không tìm thấy môn học nào phù hợp.';
        vungThongBao.textContent = 'Trống: Không có kết quả.';
        return;
    }

    danhSach.forEach(mon => {
        const theBai = document.createElement('article');
        theBai.className = 'the-san-pham';

        const anh = document.createElement('img');
        anh.src = mon.anh;
        anh.alt = mon.ten;
        anh.width = 400;
        anh.height = 250;
        anh.loading = 'lazy';

        const phanThan = document.createElement('div');
        phanThan.className = 'the-san-pham__than';

        const tieuDe = document.createElement('h3');
        tieuDe.textContent = mon.ten;

        const maMon = document.createElement('p');
        maMon.innerHTML = `<strong>Mã môn:</strong> ${mon.maMon}`;

        const soTinChi = document.createElement('p');
        soTinChi.innerHTML = `<strong>Số tín chỉ:</strong> ${mon.soTinChi}`;

        const trangThai = document.createElement('p');
        trangThai.innerHTML = `<strong>Trạng thái:</strong> ${mon.trangThai}`;

        const nutChiTiet = document.createElement('a');
        nutChiTiet.href = `chi-tiet.html?id=${mon.id}`;
        nutChiTiet.className = 'the-san-pham__lien-ket';
        nutChiTiet.textContent = 'Xem chi tiết →';

        phanThan.append(tieuDe, maMon, soTinChi, trangThai, nutChiTiet);
        theBai.append(anh, phanThan);
        vungChua.append(theBai);
    });

    vungThongBao.textContent = `Đang hiển thị ${danhSach.length} môn học.`;
}

// Xử lý Lọc, Tìm kiếm và Sắp xếp
function xuLyDuLieu() {
    const tuKhoa = xoaDau(oTimKiem.value.trim());
    const giaTriLoc = locTrangThai.value;
    const giaTriSapXep = sapXep.value;

    // 1. Lọc theo từ khóa và trạng thái
    let ketQua = duLieuGoc.filter(mon => {
        const thoaTuKhoa = xoaDau(mon.ten).includes(tuKhoa) || xoaDau(mon.maMon).includes(tuKhoa);
        const thoaLoc = giaTriLoc === 'tat-ca' || mon.trangThai === giaTriLoc;
        return thoaTuKhoa && thoaLoc;
    });

    // 2. Sắp xếp
    if (giaTriSapXep === 'ten-az') {
        ketQua.sort((a, b) => a.ten.localeCompare(b.ten));
    } else if (giaTriSapXep === 'tin-chi-giam') {
        ketQua.sort((a, b) => b.soTinChi - a.soTinChi);
    } else if (giaTriSapXep === 'tin-chi-tang') {
        ketQua.sort((a, b) => a.soTinChi - b.soTinChi);
    }

    hienThiDanhSach(ketQua);
}

// Khởi chạy khi tải trang
document.addEventListener('DOMContentLoaded', async () => {
    try {
        vungChua.textContent = 'Đang tải dữ liệu...'; // Trạng thái đang tải
        duLieuGoc = await taiJSON('data/mon-hoc.json');
        
        // Gắn sự kiện lắng nghe (Tìm kiếm tức thời)
        oTimKiem.addEventListener('input', xuLyDuLieu);
        locTrangThai.addEventListener('change', xuLyDuLieu);
        sapXep.addEventListener('change', xuLyDuLieu);
        
        // Xóa hành vi gửi form mặc định để không bị tải lại trang
        document.querySelector('.form-lien-he').addEventListener('submit', (e) => e.preventDefault());

        xuLyDuLieu(); // Render lần đầu
    } catch (loi) {
    console.error(loi);

    vungChua.textContent = "";

    const thongBaoLoi = document.createElement("p");
    thongBaoLoi.className = "thong-bao-loi";
    thongBaoLoi.textContent = "Không thể tải dữ liệu môn học.";

    const nutThuLai = document.createElement("button");
    nutThuLai.type = "button";
    nutThuLai.textContent = "Thử lại";

    nutThuLai.addEventListener("click", () => {
        window.location.reload();
    });

    vungChua.append(thongBaoLoi, nutThuLai);
    vungThongBao.textContent = "Lỗi: Không tải được dữ liệu.";
}
});
