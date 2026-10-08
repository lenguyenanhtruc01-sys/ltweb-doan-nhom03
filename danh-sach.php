<?php
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/header.php';
?>

<style>
/* ===== CSS Tổng thể & Biến màu sắc ===== */
:root {
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --accent: #0f766e;
    --accent-hover: #0d9488;
    --bg-main: #f8fafc;
    --card-bg: #ffffff;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --radius: 10px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
}

body {
    background-color: var(--bg-main);
    color: var(--text-main);
}

.noi-dung-chinh {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.noi-dung-chinh > h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 25px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 12px;
}

/* ===== Khu vực Bộ lọc Tra cứu ===== */
.section-bo-loc {
    background: var(--card-bg);
    padding: 24px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 35px;
}

.section-bo-loc h2 {
    font-size: 18px;
    margin-bottom: 15px;
    color: #334155;
}

.section-bo-loc p {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.form-tim-kiem {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

@media (max-width: 900px) {
    .form-tim-kiem {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .form-tim-kiem {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

.form-control {
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    color: var(--text-main);
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-loc {
    padding: 10px 24px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    height: 42px;
}

.btn-loc:hover {
    background: var(--primary-hover);
}

/* ===== Lưới Môn học (Grid) ===== */
.tieu-de-phan {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #334155;
}

.luoi-san-pham {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.the-san-pham {
    background: var(--card-bg);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
}

.the-san-pham:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.the-san-pham img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: #f1f5f9;
    border-bottom: 1px solid var(--border-color);
}

.the-san-pham__than {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.the-san-pham__than h3 {
    font-size: 17px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 10px;
    line-height: 1.4;
}

.the-san-pham__than p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 6px;
}

.the-san-pham__than p strong {
    color: #334155;
}

/* ===== Khu vực nút thao tác bên trong thẻ ===== */
.the-san-pham__hanh-dong {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: auto;
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
}

.the-san-pham__o-xanh {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #eff6ff;
    padding: 8px 12px;
    border-radius: 6px;
}

.the-san-pham__lien-ket {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}

.the-san-pham__lien-ket:hover {
    text-decoration: underline;
}

/* Nút yêu thích dạng trái tim */
.nut-yeu-thich {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s;
}

.nut-yeu-thich:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

.nut-yeu-thich--da-chon {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

/* Nút thêm vào kế hoạch học tập */
.nut-them-ke-hoach {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 6px;
    background: var(--accent);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
    text-align: center;
}

.nut-them-ke-hoach:hover {
    background: var(--accent-hover);
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>

<main class="noi-dung-chinh">

    <h1>Danh sách môn học</h1>

    <!-- Bộ lọc & Tra cứu -->
    <section class="section-bo-loc">
        <h2>Tra cứu và lọc môn học</h2>
        <p>
            Dưới đây là danh sách các môn học trong học kỳ 1, năm học 2026–2027. 
            Sinh viên có thể tra cứu nhanh thông tin, lọc theo trạng thái hoặc sắp xếp theo nhu cầu học tập.
        </p>

        <form class="form-tim-kiem" action="danh-sach.php" method="get">
            <div class="form-group">
                <label for="tim-kiem">Tìm kiếm môn học</label>
                <input
                    type="search"
                    id="tim-kiem"
                    name="tu-khoa"
                    class="form-control"
                    placeholder="Nhập tên hoặc mã môn...">
            </div>

            <div class="form-group">
                <label for="loc-trang-thai">Trạng thái</label>
                <select id="loc-trang-thai" name="trang-thai" class="form-control">
                    <option value="tat-ca">Tất cả</option>
                    <option value="Đang học">Đang học</option>
                    <option value="Đã hoàn thành">Đã hoàn thành</option>
                    <option value="Đăng ký mới">Đăng ký mới</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sap-xep">Sắp xếp</label>
                <select id="sap-xep" name="sap-xep" class="form-control">
                    <option value="mac-dinh">Mặc định (ID)</option>
                    <option value="ten-az">Tên môn học (A–Z)</option>
                    <option value="tin-chi-giam">Tín chỉ (Giảm dần)</option>
                    <option value="tin-chi-tang">Tín chỉ (Tăng dần)</option>
                </select>
            </div>

            <button type="submit" class="btn-loc">Lọc kết quả</button>
        </form>

        <p id="thong-bao-ket-qua" class="thong-bao-ket-qua" aria-live="polite" style="margin-top: 15px; font-weight: 500;"></p>
    </section>

    <!-- Danh sách hiển thị dạng Lưới Card -->
    <section>
        <h2 class="tieu-de-phan">Tiến độ các môn học</h2>

        <div class="luoi-san-pham">

            <!-- Môn 1 -->
            <article class="the-san-pham">
                <img src="images/int101.svg" alt="Thiết kế và Lập trình Web" width="400" height="250" loading="lazy">

                <div class="the-san-pham__than">
                    <h3>Thiết kế và Lập trình Web</h3>
                    <p><strong>Mã môn:</strong> INT101</p>
                    <p><strong>Số tín chỉ:</strong> 3</p>
                    <p><strong>Trạng thái:</strong> Đang học</p>

                    <div class="the-san-pham__hanh-dong">
                        <div class="the-san-pham__o-xanh">
                            <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=1">Xem chi tiết &rarr;</a>
                            <button
                                type="button"
                                class="nut-yeu-thich"
                                data-yeu-thich-id="1"
                                aria-pressed="false"
                                aria-label="Thêm vào yêu thích">
                                <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                            </button>
                        </div>

                        <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                            <input type="hidden" name="hanh_dong" value="them">
                            <input type="hidden" name="id" value="1">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                        </form>
                    </div>
                </div>
            </article>

            <!-- Môn 2 -->
            <article class="the-san-pham">
                <img src="images/int102.svg" alt="Hệ quản trị cơ sở dữ liệu" width="400" height="250" loading="lazy">

                <div class="the-san-pham__than">
                    <h3>Hệ quản trị cơ sở dữ liệu</h3>
                    <p><strong>Mã môn:</strong> INT102</p>
                    <p><strong>Số tín chỉ:</strong> 3</p>
                    <p><strong>Trạng thái:</strong> Đang học</p>

                    <div class="the-san-pham__hanh-dong">
                        <div class="the-san-pham__o-xanh">
                            <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=2">Xem chi tiết &rarr;</a>
                            <button
                                type="button"
                                class="nut-yeu-thich"
                                data-yeu-thich-id="2"
                                aria-pressed="false"
                                aria-label="Thêm vào yêu thích">
                                <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                            </button>
                        </div>

                        <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                            <input type="hidden" name="hanh_dong" value="them">
                            <input type="hidden" name="id" value="2">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                        </form>
                    </div>
                </div>
            </article>

            <!-- Môn 3 -->
            <article class="the-san-pham">
                <img src="images/int103.svg" alt="Khai phá dữ liệu" width="400" height="250" loading="lazy">

                <div class="the-san-pham__than">
                    <h3>Khai phá dữ liệu</h3>
                    <p><strong>Mã môn:</strong> INT103</p>
                    <p><strong>Số tín chỉ:</strong> 3</p>
                    <p><strong>Trạng thái:</strong> Đang học</p>

                    <div class="the-san-pham__hanh-dong">
                        <div class="the-san-pham__o-xanh">
                            <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=3">Xem chi tiết &rarr;</a>
                            <button
                                type="button"
                                class="nut-yeu-thich"
                                data-yeu-thich-id="3"
                                aria-pressed="false"
                                aria-label="Thêm vào yêu thích">
                                <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                            </button>
                        </div>

                        <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                            <input type="hidden" name="hanh_dong" value="them">
                            <input type="hidden" name="id" value="3">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                        </form>
                    </div>
                </div>
            </article>

            <!-- Môn 4 -->
            <article class="the-san-pham">
                <img src="images/int104.svg" alt="Công nghệ phần mềm" width="400" height="250" loading="lazy">

                <div class="the-san-pham__than">
                    <h3>Công nghệ phần mềm</h3>
                    <p><strong>Mã môn:</strong> INT104</p>
                    <p><strong>Số tín chỉ:</strong> 3</p>
                    <p><strong>Trạng thái:</strong> Đang học</p>

                    <div class="the-san-pham__hanh-dong">
                        <div class="the-san-pham__o-xanh">
                            <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=4">Xem chi tiết &rarr;</a>
                            <button
                                type="button"
                                class="nut-yeu-thich"
                                data-yeu-thich-id="4"
                                aria-pressed="false"
                                aria-label="Thêm vào yêu thích">
                                <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                            </button>
                        </div>

                        <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                            <input type="hidden" name="hanh_dong" value="them">
                            <input type="hidden" name="id" value="4">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                        </form>
                    </div>
                </div>
            </article>

            <!-- Môn 5 -->
            <article class="the-san-pham">
                <img src="images/int105.svg" alt="An toàn thông tin" width="400" height="250" loading="lazy">

                <div class="the-san-pham__than">
                    <h3>An toàn thông tin</h3>
                    <p><strong>Mã môn:</strong> INT105</p>
                    <p><strong>Số tín chỉ:</strong> 3</p>
                    <p><strong>Trạng thái:</strong> Đăng ký mới</p>

                    <div class="the-san-pham__hanh-dong">
                        <div class="the-san-pham__o-xanh">
                            <a class="the-san-pham__lien-ket" href="chi-tiet.php?id=5">Xem chi tiết &rarr;</a>
                            <button
                                type="button"
                                class="nut-yeu-thich"
                                data-yeu-thich-id="5"
                                aria-pressed="false"
                                aria-label="Thêm vào yêu thích">
                                <span class="nut-yeu-thich__icon" aria-hidden="true">♡</span>
                                <span class="visually-hidden" data-nhan-yeu-thich>Thêm vào yêu thích</span>
                            </button>
                        </div>

                        <form action="gio-hang.php" method="post" class="form-them-ke-hoach">
                            <input type="hidden" name="hanh_dong" value="them">
                            <input type="hidden" name="id" value="5">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="nut-them-ke-hoach">Thêm vào kế hoạch học tập</button>
                        </form>
                    </div>
                </div>
            </article>

        </div>
    </section>

</main>

<?php
require __DIR__ . '/inc/footer.php';
?>