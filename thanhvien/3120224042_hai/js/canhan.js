/* 
 * Tệp canhan.js - Thực hiện 2 chức năng tương tác cho trang cá nhân của Hải:
 * 1. Chuyển đổi giao diện Sáng/Tối, lưu trạng thái vào localStorage.
 * 2. Sao chép email vào clipboard (dùng navigator.clipboard.writeText) kèm thông báo.
 * Cách thử: Bấm nút "Đổi Giao Diện" hoặc nút "Copy" trên màn hình.
 */

// --- CHỨC NĂNG 1: ĐỔI GIAO DIỆN SÁNG/TỐI ---
const btnTheme = document.getElementById('btn-theme');
const bodyElement = document.body;

// Kiểm tra trạng thái lưu trong localStorage khi vừa mở trang
const currentTheme = localStorage.getItem('theme');
if (currentTheme === 'dark') {
    bodyElement.classList.add('dark-mode');
    btnTheme.textContent = 'Đổi Giao Diện ☀️';
}

// Bắt sự kiện click để chuyển đổi
btnTheme.addEventListener('click', function() {
    bodyElement.classList.toggle('dark-mode');
    
    // Cập nhật chữ trên nút và lưu vào localStorage
    if (bodyElement.classList.contains('dark-mode')) {
        btnTheme.textContent = 'Đổi Giao Diện ☀️';
        localStorage.setItem('theme', 'dark');
    } else {
        btnTheme.textContent = 'Đổi Giao Diện 🌙';
        localStorage.setItem('theme', 'light');
    }
});

// --- CHỨC NĂNG 2: SAO CHÉP EMAIL ---
const btnCopy = document.getElementById('btn-copy');
const emailText = document.getElementById('my-email').textContent;
const copyMsg = document.getElementById('copy-msg');

btnCopy.addEventListener('click', function() {
    navigator.clipboard.writeText(emailText)
        .then(function() {
            copyMsg.textContent = "Đã sao chép email thành công!";
            setTimeout(function() {
                copyMsg.textContent = "";
            }, 3000);
        })
        .catch(function(err) {
            copyMsg.textContent = "Lỗi không thể sao chép!";
            console.error('Không thể copy: ', err);
        });
});