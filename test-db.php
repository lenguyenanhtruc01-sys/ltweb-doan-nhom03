<?php
require __DIR__ . '/inc/config.php';

try {
    $db = db();
    echo "<h2 style='color:green'>✅ Kết nối CSDL thành công!</h2>";
    
    $soMon = $db->query("SELECT COUNT(*) FROM mon_hoc")->fetchColumn();
    echo "<p>Số môn học trong CSDL: <strong>$soMon</strong></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Lỗi kết nối:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}