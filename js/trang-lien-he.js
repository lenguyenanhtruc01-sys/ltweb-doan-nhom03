// js/trang-lien-he.js
// Kiểm tra dữ liệu biểu mẫu phía client (dùng ValidityState),
// hiển thị lỗi dưới từng ô khi rời ô và khi gửi,
// gửi bằng fetch POST tới jsonplaceholder, KHÔNG tải lại trang.
// Khoá nút gửi trong lúc chờ, báo kết quả qua aria-live.

const API_LIEN_HE = 'https://jsonplaceholder.typicode.com/posts';

const form = document.querySelector('#form-lien-he');
const thongBao = document.querySelector('#thong-bao-lien-he');
const nutGui = document.querySelector('#nut-gui');

// Map id ô nhập với phần tử báo lỗi tương ứng
const cacOLoi = {
  hoten: document.querySelector('#loi-hoten'),
  email: document.querySelector('#loi-email'),
  sdt: document.querySelector('#loi-sdt'),
  chude: document.querySelector('#loi-chude'),
  noidung: document.querySelector('#loi-noidung')
};

// Hiển thị / xoá lỗi cho một ô
function hienThiLoi(input, thongDiep) {
  const oLoi = cacOLoi[input.id];
  if (oLoi) oLoi.textContent = thongDiep;
  input.setAttribute('aria-invalid', thongDiep ? 'true' : 'false');
}

// Kiểm tra một ô dựa trên ValidityState
function kiemTraO(input) {
  if (input.validity.valueMissing) {
    hienThiLoi(input, 'Vui lòng nhập thông tin này.');
    return false;
  }
  if (input.validity.tooShort) {
    hienThiLoi(input, `Cần ít nhất ${input.minLength} ký tự.`);
    return false;
  }
  if (input.validity.tooLong) {
    hienThiLoi(input, `Tối đa ${input.maxLength} ký tự.`);
    return false;
  }
  if (input.validity.typeMismatch && input.type === 'email') {
    hienThiLoi(input, 'Email chưa đúng định dạng.');
    return false;
  }
  if (input.validity.patternMismatch && input.type === 'tel') {
    hienThiLoi(input, 'Số điện thoại phải bắt đầu bằng 0 và gồm 10 chữ số.');
    return false;
  }
  hienThiLoi(input, '');
  return true;
}

if (form) {
  const cacO = form.querySelectorAll('input, select, textarea');

  // Kiểm tra khi rời ô; xoá lỗi khi người dùng bắt đầu sửa
  cacO.forEach((o) => {
    o.addEventListener('blur', () => kiemTraO(o));
    o.addEventListener('input', () => {
      if (cacOLoi[o.id]) hienThiLoi(o, '');
    });
  });

  // Xử lý submit
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validate toàn bộ ô bắt buộc
    let hopLe = true;
    const cacOCanKiemTra = form.querySelectorAll(
      'input[required], select[required], textarea[required]'
    );

    cacOCanKiemTra.forEach((o) => {
      if (!kiemTraO(o)) hopLe = false;
    });

    if (!hopLe) {
      thongBao.textContent = 'Vui lòng kiểm tra lại các ô báo đỏ.';
      thongBao.className = 'thong-bao-bieu-mau loi';
      const oLoiDau = form.querySelector('[aria-invalid="true"]');
      if (oLoiDau) oLoiDau.focus();
      return;
    }

    // Khoá nút gửi trong lúc chờ
    nutGui.disabled = true;
    nutGui.textContent = 'Đang gửi...';
    thongBao.textContent = '';
    thongBao.className = 'thong-bao-bieu-mau';

    // Gom dữ liệu
    const duLieu = {
      hoTen: form.hoten.value.trim(),
      email: form.email.value.trim(),
      sdt: form.sdt.value.trim(),
      chuDe: form.chude.value,
      uuTien: form.uutien.value,
      noiDung: form.noidung.value.trim(),
      nhanPhanHoi: form.phanhoi.checked
    };

    try {
      const res = await fetch(API_LIEN_HE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(duLieu)
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      thongBao.textContent =
        'Gửi thành công! EduGPA sẽ phản hồi qua email trong vòng 24 giờ làm việc.';
      thongBao.className = 'thong-bao-bieu-mau thanh-cong';
      form.reset();
    } catch (loi) {
      console.error(loi);
      thongBao.textContent =
        'Gửi thất bại. Vui lòng kiểm tra kết nối mạng và thử lại.';
      thongBao.className = 'thong-bao-bieu-mau loi';
    } finally {
      nutGui.disabled = false;
      nutGui.textContent = 'Gửi liên hệ';
    }
  });
}