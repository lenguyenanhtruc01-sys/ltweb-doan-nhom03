// js/trang-chi-tiet.js
// Đọc tham số ?id= trên URL, tải data/mon-hoc.json,
// tìm môn học theo id và render vào #chi-tiet.
// Đủ 3 trạng thái: đang tải – lỗi – không tìm thấy.
// Đổi document.title theo tên môn.

import { taiJSON } from './api.js';

const khung = document.querySelector('#chi-tiet');

// Lấy id từ URL: chi-tiet.html?id=3
const thamSo = new URLSearchParams(location.search);
const id = Number(thamSo.get('id'));

// Hiển thị trạng thái (đang tải / lỗi / rỗng)
function hienThiTrangThai(noiDung) {
  if (!khung) return;
  khung.textContent = '';
  const p = document.createElement('p');
  p.className = 'trang-thai';
  p.textContent = noiDung;
  khung.appendChild(p);
}

async function chay() {
  if (!khung) return;

  // Thiếu id hoặc id không hợp lệ
  if (!id || Number.isNaN(id)) {
    hienThiTrangThai('Không tìm thấy môn học (thiếu tham số id).');
    return;
  }

  // Trạng thái đang tải
  hienThiTrangThai('Đang tải dữ liệu...');

  try {
    const danhSach = await taiJSON('data/mon-hoc1.json');
    const mon = danhSach.find((x) => x.id === id);

    // Không tìm thấy
    if (!mon) {
      hienThiTrangThai(
        `Không tìm thấy môn học có id = ${id}. Vui lòng kiểm tra lại đường dẫn.`
      );
      return;
    }

    // Đổi tiêu đề trang
    document.title = `${mon.ten} | EduGPA`;

    // Xoá nội dung cũ
    khung.textContent = '';

    // Tiêu đề môn
    const h2 = document.createElement('h2');
    h2.textContent = mon.ten;
    khung.appendChild(h2);

    // Ảnh minh hoạ
    const hinh = document.createElement('img');
    hinh.src = mon.hinhAnh;
    hinh.alt = `Minh hoạ môn ${mon.ten}`;
    hinh.loading = 'lazy';
    khung.appendChild(hinh);

    // Danh sách thông tin
    const ul = document.createElement('ul');
    ul.className = 'thong-tin-mon';

    const thongTin = [
      ['Mã môn', mon.maMon],
      ['Số tín chỉ', mon.soTinChi],
      ['Giảng viên', mon.giangVien],
      ['Học kỳ', mon.hocKy],
      ['Điểm chữ', mon.diemChu],
      ['Điểm hệ 4', mon.diemHe4],
      ['Trạng thái', mon.trangThai]
    ];

    thongTin.forEach(([nhan, giaTri]) => {
      const li = document.createElement('li');
      const strong = document.createElement('strong');
      strong.textContent = `${nhan}: `;
      li.appendChild(strong);
      li.appendChild(document.createTextNode(String(giaTri)));
      ul.appendChild(li);
    });

    khung.appendChild(ul);

    // Mô tả
    const moTa = document.createElement('p');
    moTa.textContent = mon.moTa;
    khung.appendChild(moTa);

    // Nút quay lại danh sách
    const nutQuayLai = document.createElement('a');
    nutQuayLai.href = 'danh-sach.html';
    nutQuayLai.className = 'nut-quay-lai';
    nutQuayLai.textContent = '← Quay lại danh sách môn học';
    khung.appendChild(nutQuayLai);
  } catch (loi) {
    console.error(loi);
    hienThiTrangThai(
      'Không tải được dữ liệu. Vui lòng kiểm tra kết nối mạng và thử lại.'
    );
  }
}

chay();
