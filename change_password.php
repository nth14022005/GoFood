<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'Khách';
$message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Vui lòng điền đầy đủ các trường.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } else {
        // Lấy mật khẩu hiện tại của người dùng từ CSDL
        $stmt_get_pass = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        if (!$stmt_get_pass) {
            $error_message = "Lỗi hệ thống (prepare get pass).";
        } else {
            $stmt_get_pass->bind_param("i", $user_id);
            $stmt_get_pass->execute();
            $result_get_pass = $stmt_get_pass->get_result();
            $user_data = $result_get_pass->fetch_assoc();
            $stmt_get_pass->close();

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Mật khẩu hiện tại đúng, tiến hành cập nhật mật khẩu mới
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                if (!$stmt_update_pass) {
                     $error_message = "Lỗi hệ thống (prepare update pass).";
                } else {
                    $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
                    if ($stmt_update_pass->execute()) {
                        $message = "Đổi mật khẩu thành công!";
                    } else {
                        $error_message = "Lỗi khi cập nhật mật khẩu: " . $stmt_update_pass->error;
                    }
                    $stmt_update_pass->close();
                }
            } else {
                $error_message = "Mật khẩu hiện tại không đúng.";
            }
        }
    }
}

// Lấy số lượng hàng trong giỏ
$cart_item_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt_cart = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
    if ($stmt_cart) {
        $stmt_cart->bind_param("i", $_SESSION['user_id']);
        $stmt_cart->execute();
        $result_cart_count = $stmt_cart->get_result();
        if ($row_cart_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_cart_count['total_items'] ?? 0);
        }
        $stmt_cart->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi Mật Khẩu - GoFood</title>
    <link rel="stylesheet" href="css.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding-top: 70px; }
        .container { width: 80%; max-width: 500px; margin: 30px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); }
        .container h1 { text-align: center; margin-bottom: 25px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 1em; }
        .message, .error-message { padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .submit-btn { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em; transition: background-color 0.3s ease; display: block; width: auto; margin: 0 auto; }
        .submit-btn:hover { background-color: #218838; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        header { background-color: #ffcc00; padding: 10px 5%; display: flex; justify-content: space-between; align-items: center; position: fixed; top: 0; left: 0; width: 90%; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .logo img { height: 50px; }
        .nav-user-wrapper { display: flex; align-items: center; justify-content: space-between; flex: 1; margin-left: 30px; }
        nav ul.navbar { list-style: none; padding: 0; margin: 0; display: flex; }
        nav ul.navbar li { margin-left: 25px; }
        nav ul.navbar li a { text-decoration: none; color: #333; font-weight: bold; padding: 5px 10px; border-radius: 5px; transition: background-color 0.3s ease, color 0.3s ease; }
        nav ul.navbar li a:hover, nav ul.navbar li a.active { background-color: #28a745; color: white; }
        .user-actions { display: flex; align-items: center; }
        .user-actions .username-display, .user-actions .cart-button, .user-actions .logout-button { color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; margin-left: 15px; font-weight: bold; transition: background-color 0.3s ease; border: none; cursor: pointer; font-size: 0.9em; }
        .user-actions .username-display { background-color: #e65c00; }
        .user-actions .cart-button { background-color: #d32f2f; position: relative; }
        .user-actions .cart-button .cart-count { position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white; }
        .user-actions .logout-button { background-color: #555; }
        .user-actions .username-display:hover, .user-actions .cart-button:hover, .user-actions .logout-button:hover { opacity: 0.9; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <a href="index.php"><img src="image/Go Food.png" alt="Go Food Logo"></a>
    </div>
    <div class="nav-user-wrapper">
        <nav>
            <ul class="navbar">
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="user_profile.php">Hồ sơ của tôi</a></li>
                <li><a href="order_history.php">Lịch sử mua hàng</a></li>
            </ul>
        </nav>
        <div class="user-actions">
            <?php if ($current_username !== 'Khách'): ?>
                <a href="user_profile.php" class="username-display"><?php echo "Xin chào, " . htmlspecialchars($current_username); ?></a>
                <button onclick="toggleCart()" class="cart-button">
                    🛒 Giỏ hàng
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-count" id="cartCount" style="position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white;"><?php echo $cart_item_count; ?></span>
                    <?php else: ?>
                        <span class="cart-count" id="cartCount" style="position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white; display: none;">0</span>
                    <?php endif; ?>
                </button>
                <a href="logout.php" class="logout-button">Đăng xuất</a>
            <?php else: ?>
                <a href="login.php" class="logout-button">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <h1>Đổi Mật Khẩu</h1>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="change_password.php" method="POST">
        <div class="form-group">
            <label for="current_password">Mật khẩu hiện tại:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">Mật khẩu mới:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu mới:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="submit-btn">Đổi Mật Khẩu</button>
    </form>
    <a href="user_profile.php" class="back-link">Quay lại hồ sơ</a>
</div>

<script>
    function toggleCart() {
        var cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.style.display = cartModal.style.display === 'none' || cartModal.style.display === '' ? 'block' : 'none';
        }
    }
</script>

</body>
</html> 