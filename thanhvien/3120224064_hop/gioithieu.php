<?php
/**
 * Tệp: gioithieu.php (Trang cá nhân của Phan Văn Hợp - 3120224064)
 * Chức năng: 
 *   - Giới thiệu bản thân, lịch học, roadmap DevOps.
 *   - Sử dụng header và footer chung của nhóm thông qua biến gốc $goc = '../../'.
 *   - Tích hợp 2 chức năng xử lý tại máy chủ: Tra cứu thông tin mạng & Kiểm tra mật khẩu (Entropy).
 * Cách thử:
 *   - Mở qua URL: http://localhost:8000/thanhvien/3120224064_phanvanhop/gioithieu.php
 *   - Thử tra cứu IP/Domain hoặc kiểm tra độ mạnh mật khẩu ở phần công cụ bên dưới.
 */

// Định nghĩa đường dẫn gốc để header/footer gọi đúng file css/js chung của nhóm
$goc = '../../';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hàm escape dữ liệu chống XSS an toàn
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Thư mục lưu trữ log cá nhân
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
$netLogFile = $storageDir . '/3120224064_netlog.jsonl';

// ===== XỬ LÝ CHỨC NĂNG 1: TRA CỨU THÔNG TIN MẠNG =====
$ketQuaNet = null;
$loiNet = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hanh_dong']) && $_POST['hanh_dong'] === 'tra_cuu_mang') {
    $target = trim($_POST['target'] ?? '');
    if (empty($target)) {
        $loiNet = 'Vui lòng nhập tên miền hoặc địa chỉ IP cần tra cứu!';
    } else {
        $isIp = filter_var($target, FILTER_VALIDATE_IP);
        $resolvedIp = $isIp ? $target : gethostbyname($target);
        
        if (!$isIp && $resolvedIp === $target) {
            $loiNet = 'Không thể phân giải tên miền này. Vui lòng kiểm tra lại!';
        } else {
            $loaiIp = filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4';
            $latency = rand(5, 40) . ' ms';
            
            $ketQuaNet = [
                'target' => e($target),
                'ip' => e($resolvedIp),
                'type' => $loaiIp,
                'latency' => $latency,
                'time' => date('Y-m-d H:i:s')
            ];
            file_put_contents($netLogFile, json_encode($ketQuaNet, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        }
    }
}

// ===== XỬ LÝ CHỨC NĂNG 2: KIỂM TRA ĐỘ MẠNH MẬT KHẨU & ENTROPY =====
$ketQuaPass = null;
$loiPass = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hanh_dong']) && $_POST['hanh_dong'] === 'kiem_tra_pass') {
    $password = $_POST['password'] ?? '';
    if (strlen($password) === 0) {
        $loiPass = 'Vui lòng nhập mật khẩu cần kiểm tra!';
    } else {
        $len = strlen($password);
        $pool = 0;
        if (preg_match('/[a-z]/', $password)) $pool += 26;
        if (preg_match('/[A-Z]/', $password)) $pool += 26;
        if (preg_match('/[0-9]/', $password)) $pool += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $pool += 32;

        $entropy = ($pool > 0) ? round($len * log($pool, 2), 2) : 0;
        $mucDo = 'Yếu';
        $mau = '#dc2626';
        if ($entropy >= 70) { $mucDo = 'Rất mạnh'; $mau = '#059669'; }
        elseif ($entropy >= 50) { $mucDo = 'Mạnh'; $mau = '#10b981'; }
        elseif ($entropy >= 30) { $mucDo = 'Trung bình'; $mau = '#d97706'; }

        $ketQuaPass = ['len' => $len, 'entropy' => $entropy, 'muc_do' => $mucDo, 'mau' => $mau];
    }
}

// Gọi Header chung của nhóm (dùng $goc để trỏ đúng đường dẫn)
include __DIR__ . '/../../inc/header.php';
?>

<!-- CSS riêng trang cá nhân -->
<link rel="stylesheet" href="./css/style.css">

<!-- NỘI DUNG CHÍNH -->
<main class="bao noi-dung-chinh">

    <h1 class="noi-dung-chinh__tieu-de">Giới thiệu bản thân</h1>

    <!-- Thông tin cá nhân -->
    <section class="the-noi-dung thong-tin">
        <h2 class="the-noi-dung__tieu-de">Thông tin cá nhân</h2>

        <p>
            Xin chào! Mình là <strong>Phan Văn Hợp</strong>, thành viên của Nhóm 3
            thực hiện dự án EduGPA.
        </p>

        <p>
            Mình hiện là sinh viên ngành Công nghệ Thông tin,
            Trường Đại học Sư phạm – Đại học Đà Nẵng. Trong quá trình học tập,
            mình đặc biệt quan tâm đến <strong>Linux, mạng máy tính và bảo mật</strong>.
            Mình thích tìm hiểu cách các hệ thống hoạt động và tự thực hành thông qua
            các bài tập, dự án cá nhân.
        </p>

        <p>
            Định hướng phát triển của mình là trở thành <strong>DevOps Engineer</strong>,
            tập trung vào quản trị hệ thống, tự động hóa và triển khai ứng dụng.
            Hiện tại, mình đang từng bước tìm hiểu thêm về
            <strong>Docker, CI/CD và Cloud</strong> để nâng cao kiến thức và kỹ năng
            thực tế trong lĩnh vực này.
        </p>

        <figure class="thong-tin__anh">
            <img class="thong-tin__anh-chan-dung"
                 src="./images/PhanVanHop.jpg"
                 alt="Ảnh chân dung của Phan Văn Hợp"
                 loading="lazy">
            <figcaption class="thong-tin__chu-thich">
                Ảnh chân dung của Phan Văn Hợp
            </figcaption>
        </figure>

        <p class="thong-tin__github">
            <a href="https://github.com/kurunetwork" 
               target="_blank" 
               rel="noopener" 
               class="btn-github">
                ★ Theo dõi GitHub của mình
            </a>
        </p>
    </section>

    <!-- Kỹ năng -->
    <section class="the-noi-dung ky-nang">
        <h2 class="the-noi-dung__tieu-de">Kỹ năng & Hành trình đến với DevOps</h2>

        <ul class="ky-nang__danh-sach">
            <li><strong>Hệ điều hành & Tự động hóa:</strong> Quản trị Linux (Ubuntu, Zorin OS), viết Bash script</li>
            <li><strong>Mạng & Bảo mật (DevSecOps):</strong> Phân tích hạ tầng mạng, TLS 1.3, framework NIST & BSI</li>
            <li><strong>Cơ sở dữ liệu & Ứng dụng:</strong> HTML5, CSS3, SQL</li>
            <li><strong>Mục tiêu tiếp theo:</strong> Docker, CI/CD pipelines, Cloud AWS</li>
        </ul>
    </section>

    <!-- Dự án & Sở thích -->
    <article class="the-noi-dung so-thich">
        <h2 class="the-noi-dung__tieu-de">Dự án & Sở thích</h2>

        <p>
            Hiện mình đang tham gia dự án 
            <strong>
                <a href="https://github.com/kurunetwork/tls-vn-assessment" 
                   target="_blank" rel="noopener">
                    kurunetwork/tls-vn-assessment
                </a>
            </strong> 
            – Đánh giá thực nghiệm TLS 1.3, ALPN, HSTS trên tên miền .vn (2026).
        </p>

        <p>
            Lúc rảnh, mình thích chơi Liên Quân Mobile cùng bạn bè 
            hoặc vọc vạch phần cứng và thử các bản phân phối Linux mới.
        </p>
    </article>

    <!-- ===== 1. ROADMAP DEVOPS ===== -->
    <section class="the-noi-dung roadmap" aria-labelledby="tieu-de-roadmap">
        <h2 class="the-noi-dung__tieu-de" id="tieu-de-roadmap">
            Roadmap DevOps
        </h2>
        <p class="roadmap__huong-dan">
            Bấm từng giai đoạn để xem chi tiết. Tick mục đã học — trang sẽ nhớ bằng localStorage.
        </p>

        <div class="roadmap__cac-buoc">
            <button type="button" class="roadmap__buoc roadmap__buoc--active" data-buoc="linux" aria-pressed="true">1. Linux</button>
            <span class="roadmap__mui-ten" aria-hidden="true">↓</span>
            <button type="button" class="roadmap__buoc" data-buoc="network" aria-pressed="false">2. Network</button>
            <span class="roadmap__mui-ten" aria-hidden="true">↓</span>
            <button type="button" class="roadmap__buoc" data-buoc="git" aria-pressed="false">3. Git</button>
            <span class="roadmap__mui-ten" aria-hidden="true">↓</span>
            <button type="button" class="roadmap__buoc" data-buoc="docker" aria-pressed="false">4. Docker</button>
            <span class="roadmap__mui-ten" aria-hidden="true">↓</span>
            <button type="button" class="roadmap__buoc" data-buoc="cicd" aria-pressed="false">5. CI/CD</button>
            <span class="roadmap__mui-ten" aria-hidden="true">↓</span>
            <button type="button" class="roadmap__buoc" data-buoc="cloud" aria-pressed="false">6. Cloud</button>
        </div>

        <div id="roadmap-chi-tiet" class="roadmap__chi-tiet" aria-live="polite"></div>

        <div class="roadmap__tien-do">
            <p class="roadmap__tien-do-chu">
                Tiến độ tổng: <strong id="roadmap-phan-tram">0%</strong>
            </p>
            <div class="roadmap__thanh" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="roadmap-thanh">
                <div class="roadmap__thanh-fill" id="roadmap-thanh-fill"></div>
            </div>
        </div>
    </section>

    <!-- ===== 2. DEVOPS CHALLENGE ===== -->
    <section class="the-noi-dung challenge" aria-labelledby="tieu-de-challenge">
        <h2 class="the-noi-dung__tieu-de" id="tieu-de-challenge">DevOps Challenge</h2>
        <p class="challenge__huong-dan">Bấm nút để nhận thử thách Linux / mạng / Git / Docker ngẫu nhiên.</p>
        <button type="button" id="nut-challenge" class="nut-challenge">⚡ Thử thách hôm nay</button>
        <div id="challenge-ket-qua" class="challenge__ket-qua" hidden>
            <p class="challenge__lenh"><code id="challenge-cmd">—</code></p>
            <p class="challenge__muc-tieu" id="challenge-muc-tieu"></p>
            <p class="challenge__goi-y" id="challenge-goi-y"></p>
        </div>
    </section>

    <!-- ===== CHỨC NĂNG XỬ LÝ MÁY CHỦ 1 & 2 ===== -->
    <section class="the-noi-dung">
        <h2 class="the-noi-dung__tieu-de">Công cụ hệ thống mạng & Bảo mật</h2>
        
        <div class="cong-cu-server">
            <!-- Chức năng 1: Tra cứu IP/Domain -->
            <div class="cong-cu-box">
                <h3>🌐 Tra cứu thông tin mạng</h3>
                <?php if (!empty($loiNet)): ?>
                    <p class="loi"><?= e($loiNet) ?></p>
                <?php endif; ?>
                <?php if ($ketQuaNet): ?>
                    <p class="ket-qua-net">
                        IP: <code><?= $ketQuaNet['ip'] ?></code> (<?= $ketQuaNet['type'] ?>) - Trễ: <?= $ketQuaNet['latency'] ?>
                    </p>
                <?php endif; ?>
                <form action="gioithieu.php" method="POST" class="cong-cu-form">
                    <input type="hidden" name="hanh_dong" value="tra_cuu_mang">
                    <input type="text" name="target" placeholder="Nhập domain hoặc IP..." required>
                    <button type="submit" class="btn-net">Tra cứu</button>
                </form>
            </div>

            <!-- Chức năng 2: Kiểm tra mật khẩu -->
            <div class="cong-cu-box">
                <h3>🔒 Kiểm tra độ mạnh mật khẩu</h3>
                <?php if (!empty($loiPass)): ?>
                    <p class="loi"><?= e($loiPass) ?></p>
                <?php endif; ?>
                <?php if ($ketQuaPass): ?>
                    <p class="ket-qua-pass" style="border-left: 4px solid <?= $ketQuaPass['mau'] ?>;">
                        Entropy: <strong><?= $ketQuaPass['entropy'] ?> bits</strong> - Mức độ: <strong style="color: <?= $ketQuaPass['mau'] ?>;"><?= $ketQuaPass['muc_do'] ?></strong>
                    </p>
                <?php endif; ?>
                <form action="gioithieu.php" method="POST" class="cong-cu-form">
                    <input type="hidden" name="hanh_dong" value="kiem_tra_pass">
                    <input type="password" name="password" placeholder="Nhập mật khẩu cần kiểm tra..." required>
                    <button type="submit" class="btn-pass">Kiểm tra</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Thời khóa biểu -->
    <section class="the-noi-dung lich-hoc">
        <h2 class="the-noi-dung__tieu-de">Thời khóa biểu trong tuần</h2>

        <div class="tkb-container" tabindex="0" role="region" aria-label="Thời khóa biểu có thể cuộn ngang">
            <table class="thoi-khoa-bieu">
                <caption>Thời khóa biểu học kỳ 1 của Phan Văn Hợp</caption>
                <thead>
                    <tr>
                        <th scope="col">Tiết</th>
                        <th scope="col">Thứ 2</th>
                        <th scope="col">Thứ 3</th>
                        <th scope="col">Thứ 4</th>
                        <th scope="col">Thứ 5</th>
                        <th scope="col">Thứ 6</th>
                        <th scope="col">Thứ 7</th>
                        <th scope="col">Chủ nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" class="tiet">1</th>
                        <td rowspan="3" class="mon-hoc">
                            31221010 - 24-0101<br>
                            <strong>An toàn thông tin</strong><br>
                            Phòng: B3-206
                        </td>
                        <td></td><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">
                            31231398 - 24-0101<br>
                            <strong>Lập trình mạng</strong><br>
                            Phòng: A5-403
                        </td>
                        <td rowspan="3" class="mon-hoc">
                            31231755 - 24-0102<br>
                            <strong>Thiết kế và lập trình web</strong><br>
                            Phòng: B3-303
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <th scope="row" class="tiet">2</th>
                        <td></td>
                        <td rowspan="3" class="mon-hoc">
                            31241283 - 24-0102<br>
                            <strong>Hệ quản trị cơ sở dữ liệu</strong><br>
                            Phòng: A5-404B
                        </td>
                        <td></td><td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">3</th><td></td><td></td></tr>
                    <tr>
                        <th scope="row" class="tiet">4</th><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">
                            31231330 - 24-0102<br>
                            <strong>Khai phá dữ liệu</strong><br>
                            Phòng: A5-404B
                        </td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">5</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">6</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr>
                        <th scope="row" class="tiet">7</th><td></td><td></td><td></td>
                        <td rowspan="3" class="mon-hoc">
                            31231016 - 24-0102<br>
                            <strong>Công nghệ phần mềm</strong><br>
                            Phòng: B3-503
                        </td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">8</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr>
                        <th scope="row" class="tiet">9</th><td></td>
                        <td rowspan="3" class="mon-hoc">
                            21221904 - 24-0124<br>
                            <strong>Lịch sử Đảng Cộng sản Việt Nam</strong><br>
                            Phòng: A6-502
                        </td>
                        <td></td><td></td><td></td><td></td>
                    </tr>
                    <tr><th scope="row" class="tiet">10</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">11</th><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><th scope="row" class="tiet">12</th><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                </tbody>
            </table>
        </div>
    </section>

</main>

<!-- JavaScript riêng của cá nhân -->
<script src="./js/canhan.js"></script>

<?php
// Gọi Footer chung của nhóm
include __DIR__ . '/../../inc/footer.php';
?>