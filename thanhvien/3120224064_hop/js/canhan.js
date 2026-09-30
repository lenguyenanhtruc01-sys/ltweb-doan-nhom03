/**
 * canhan.js – Tương tác trang cá nhân Phan Văn Hợp
 * 1) Roadmap DevOps: bấm giai đoạn, tick đã học, % tiến độ + localStorage
 * 2) DevOps Challenge: nút random thử thách lệnh Linux/Git/Docker.
 * Cách thử: bấm Linux→Docker, tick checkbox, F5 xem còn nhớ;
 * bấm "Thử thách hôm nay"; Tab+Enter; thử width 360px.
 */

(function () {
    "use strict";

    var KEY_ROADMAP = "hop-roadmap-progress";

    /* ----- Dữ liệu roadmap ----- */
    var ROADMAP = {
        linux: {
            ten: "Linux",
            icon: "🐧",
            items: [
                { id: "linux-1", text: "Cài & dùng Ubuntu / Zorin OS" },
                { id: "linux-2", text: "Làm việc với terminal (cd, ls, grep)" },
                { id: "linux-3", text: "Viết Bash script cơ bản" },
                { id: "linux-4", text: "Phân quyền file (chmod, chown)" }
            ]
        },
        network: {
            ten: "Network & Bảo mật",
            icon: "🌐",
            items: [
                { id: "net-1", text: "Hiểu TCP/IP, DNS, HTTP" },
                { id: "net-2", text: "TLS 1.3, ALPN, HSTS" },
                { id: "net-3", text: "Phân tích bằng ss, ping, traceroute" },
                { id: "net-4", text: "Framework NIST / BSI (khái niệm)" }
            ]
        },
        git: {
            ten: "Git",
            icon: "📦",
            items: [
                { id: "git-1", text: "clone, add, commit, push" },
                { id: "git-2", text: "Nhánh (branch) & merge" },
                { id: "git-3", text: "Đọc diff / log" }
            ]
        },
        docker: {
            ten: "Docker",
            icon: "🐳",
            items: [
                { id: "docker-1", text: "Image & Container" },
                { id: "docker-2", text: "Dockerfile cơ bản" },
                { id: "docker-3", text: "Docker Compose" },
                { id: "docker-4", text: "Networking container" }
            ]
        },
        cicd: {
            ten: "CI/CD",
            icon: "⚙️",
            items: [
                { id: "cicd-1", text: "Khái niệm pipeline" },
                { id: "cicd-2", text: "GitHub Actions / GitLab CI (làm quen)" },
                { id: "cicd-3", text: "Build → Test → Deploy" }
            ]
        },
        cloud: {
            ten: "Cloud (AWS)",
            icon: "☁️",
            items: [
                { id: "cloud-1", text: "Khái niệm IaaS / PaaS" },
                { id: "cloud-2", text: "EC2 / S3 (làm quen)" },
                { id: "cloud-3", text: "Deploy ứng dụng đơn giản" }
            ]
        }
    };

    /* ----- Challenge ----- */
    var CHALLENGES = [
        {
            cmd: "ip addr",
            mucTieu: "Xác định địa chỉ IP của máy.",
            goiY: "Tìm dòng inet trong output (thường là 192.168.x.x)."
        },
        {
            cmd: "ping -c 4 1.1.1.1",
            mucTieu: "Kiểm tra kết nối mạng tới Cloudflare DNS.",
            goiY: "Xem RTT (ms) và % packet loss."
        },
        {
            cmd: "ss -tuln",
            mucTieu: "Liệt kê cổng đang lắng nghe (listening).",
            goiY: "Thay netstat cổ điển trên Linux hiện đại."
        },
        {
            cmd: "curl -I https://example.com",
            mucTieu: "Xem header HTTP (status, server, HSTS…).",
            goiY: "Chú ý dòng HTTP/2 200 và Strict-Transport-Security."
        },
        {
            cmd: "git status",
            mucTieu: "Xem trạng thái repo Git hiện tại.",
            goiY: "Chạy trong thư mục có .git."
        },
        {
            cmd: "docker ps",
            mucTieu: "Liệt kê container đang chạy.",
            goiY: "Cần cài Docker; nếu chưa có, ghi lại lỗi để tra."
        },
        {
            cmd: "systemctl status ssh",
            mucTieu: "Kiểm tra dịch vụ SSH có đang chạy không.",
            goiY: "Trên một số distro service tên là sshd."
        },
        {
            cmd: "nslookup google.com",
            mucTieu: "Phân giải DNS tên miền.",
            goiY: "So sánh với dig google.com nếu có."
        },
        {
            cmd: "df -h",
            mucTieu: "Xem dung lượng ổ đĩa còn trống.",
            goiY: "Cột Use% cho biết phân vùng nào sắp đầy."
        },
        {
            cmd: "uname -a",
            mucTieu: "Xem kernel và thông tin hệ thống.",
            goiY: "Hữu ích khi báo cáo môi trường lab."
        }
    ];

    /* ========== localStorage ========== */
    function docProgress() {
        try {
            var raw = localStorage.getItem(KEY_ROADMAP);
            if (!raw) return {};
            var obj = JSON.parse(raw);
            return typeof obj === "object" && obj ? obj : {};
        } catch (e) {
            return {};
        }
    }

    function luuProgress(map) {
        localStorage.setItem(KEY_ROADMAP, JSON.stringify(map));
    }

    var progress = docProgress();

    /* ========== Roadmap UI ========== */
    var chiTiet = document.getElementById("roadmap-chi-tiet");
    var elPhanTram = document.getElementById("roadmap-phan-tram");
    var elThanh = document.getElementById("roadmap-thanh");
    var elThanhFill = document.getElementById("roadmap-thanh-fill");
    var cacNutBuoc = document.querySelectorAll(".roadmap__buoc");

    function demTongItem() {
        var tong = 0;
        Object.keys(ROADMAP).forEach(function (k) {
            tong += ROADMAP[k].items.length;
        });
        return tong;
    }

    function demDaHoc() {
        var dem = 0;
        Object.keys(ROADMAP).forEach(function (k) {
            ROADMAP[k].items.forEach(function (it) {
                if (progress[it.id]) dem += 1;
            });
        });
        return dem;
    }

    function capNhatTienDo() {
        var tong = demTongItem();
        var da = demDaHoc();
        var pct = tong === 0 ? 0 : Math.round((da / tong) * 100);

        if (elPhanTram) elPhanTram.textContent = pct + "%";
        if (elThanhFill) elThanhFill.style.width = pct + "%";
        if (elThanh) elThanh.setAttribute("aria-valuenow", String(pct));
    }

    function renderBuoc(key) {
        if (!chiTiet || !ROADMAP[key]) return;

        var data = ROADMAP[key];
        chiTiet.textContent = "";

        var tieuDe = document.createElement("h3");
        tieuDe.className = "roadmap__ten-buoc";
        tieuDe.textContent = data.icon + " " + data.ten;
        chiTiet.appendChild(tieuDe);

        var ul = document.createElement("ul");
        ul.className = "roadmap__list";

        data.items.forEach(function (it) {
            var li = document.createElement("li");
            li.className = "roadmap__item";

            var label = document.createElement("label");
            label.className = "roadmap__label";

            var cb = document.createElement("input");
            cb.type = "checkbox";
            cb.className = "roadmap__checkbox";
            cb.checked = !!progress[it.id];
            cb.setAttribute("data-id", it.id);

            var span = document.createElement("span");
            span.textContent = it.text;

            label.appendChild(cb);
            label.appendChild(span);
            li.appendChild(label);
            ul.appendChild(li);

            cb.addEventListener("change", function () {
                if (cb.checked) {
                    progress[it.id] = true;
                } else {
                    delete progress[it.id];
                }
                luuProgress(progress);
                capNhatTienDo();
            });
        });

        chiTiet.appendChild(ul);
    }

    function chonBuoc(key) {
        cacNutBuoc.forEach(function (nut) {
            var active = nut.getAttribute("data-buoc") === key;
            nut.classList.toggle("roadmap__buoc--active", active);
            nut.setAttribute("aria-pressed", active ? "true" : "false");
        });
        renderBuoc(key);
    }

    cacNutBuoc.forEach(function (nut) {
        nut.addEventListener("click", function () {
            var key = nut.getAttribute("data-buoc");
            if (key) chonBuoc(key);
        });
    });

    /* Khởi tạo roadmap */
    chonBuoc("linux");
    capNhatTienDo();

    /* ========== DevOps Challenge ========== */
    var nutChallenge = document.getElementById("nut-challenge");
    var boxKetQua = document.getElementById("challenge-ket-qua");
    var elCmd = document.getElementById("challenge-cmd");
    var elMucTieu = document.getElementById("challenge-muc-tieu");
    var elGoiY = document.getElementById("challenge-goi-y");
    var lastIndex = -1;

    function randomChallenge() {
        if (CHALLENGES.length === 0) return;

        var idx;
        do {
            idx = Math.floor(Math.random() * CHALLENGES.length);
        } while (CHALLENGES.length > 1 && idx === lastIndex);

        lastIndex = idx;
        var c = CHALLENGES[idx];

        if (elCmd) elCmd.textContent = "$ " + c.cmd;
        if (elMucTieu) elMucTieu.textContent = "🎯 Mục tiêu: " + c.mucTieu;
        if (elGoiY) elGoiY.textContent = "💡 Gợi ý: " + c.goiY;
        if (boxKetQua) boxKetQua.hidden = false;
    }

    if (nutChallenge) {
        nutChallenge.addEventListener("click", randomChallenge);
    }
})();