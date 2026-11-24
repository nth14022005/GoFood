<?php
// Version 1.0.1 - Forcing a refresh
include 'db_connect.php';  // Kết nối database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // 1. Kiểm tra email có tồn tại trong bảng users không
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Email không tồn tại trong hệ thống.";
        exit;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];

    // 2. Tạo token reset
    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    // 3. Lưu token vào bảng password_resets
    $sql_insert = "INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iss", $user_id, $token, $expires_at);
    $stmt_insert->execute();

    // 4. Tạo link reset mật khẩu
    $reset_link = "http://localhost/gofood/reset_password.php?token=" . $token;  // Thay localhost/gofood thành URL thật của bạn

    // 5. Hiển thị link reset đẹp cho test
    echo '<div style="
        margin: 30px auto; 
        padding: 20px; 
        max-width: 600px; 
        background-color: #1e1e1e; 
        border-radius: 8px; 
        box-shadow: 0 0 15px rgba(0,0,0,0.7); 
        color: #eee; 
        font-family: Arial, sans-serif;
        text-align: center;
    ">';
    echo '<h2 style="margin-bottom: 20px;">Link đặt lại mật khẩu (test)</h2>';
    echo "<a href='$reset_link' style='
        color: #28a745; 
        font-size: 18px; 
        word-break: break-all;
        text-decoration: none;
    ' target='_blank'>$reset_link</a>";
    echo '</div>';
}
?>