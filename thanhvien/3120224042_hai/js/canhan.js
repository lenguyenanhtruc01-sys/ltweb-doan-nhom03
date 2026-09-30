/* 
 * Tệp canhan.js - Thực hiện 2 chức năng tương tác KHÔNG ĐỤNG HÀNG cho trang cá nhân:
 * 1. Nút đếm lượt thả tim (Lưu vào localStorage).
 * 2. Nút ẩn/hiện bảng Thời khóa biểu.
 */

// --- CHỨC NĂNG 1: ĐẾM LƯỢT THÍCH LƯU VÀO LOCAL STORAGE ---
const btnLike = document.getElementById('btn-like');
const likeCountSpan = document.getElementById('like-count');

// Lấy số like từ bộ nhớ trình duyệt (nếu có), không có thì mặc định là 0
let currentLikes = localStorage.getItem('hai_likes_count') || 0;
likeCountSpan.textContent = currentLikes;

// Bắt sự kiện khi click vào nút Like
btnLike.addEventListener('click', function() {
    currentLikes++; // Tăng số đếm
    likeCountSpan.textContent = currentLikes; // Hiển thị ra màn hình
    localStorage.setItem('hai_likes_count', currentLikes); // Lưu lại vào máy
    
    // Hiệu ứng giật nảy nhẹ khi bấm (Scale)
    btnLike.style.transform = 'scale(1.1)';
    btnLike.style.backgroundColor = '#fff0f0';
    
    setTimeout(function() {
        btnLike.style.transform = 'scale(1)';
        btnLike.style.backgroundColor = '#fff';
    }, 200);
});


// --- CHỨC NĂNG 2: ẨN / HIỆN BẢNG THỜI KHÓA BIỂU ---
const btnToggleTkb = document.getElementById('btn-toggle-tkb');
const tkbWrapper = document.getElementById('tkb-wrapper');

btnToggleTkb.addEventListener('click', function() {
    // Nếu bảng đang hiện (hoặc chưa thiết lập display)
    if (tkbWrapper.style.display !== 'none') {
        tkbWrapper.style.display = 'none'; // Ẩn đi
        btnToggleTkb.textContent = 'Mở rộng ⬇️'; // Đổi chữ nút
    } else {
        tkbWrapper.style.display = 'block'; // Hiện lại
        btnToggleTkb.textContent = 'Thu gọn ⬆️';
    }
});