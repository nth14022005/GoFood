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

// Lấy thông tin người dùng hiện tại để điền vào form
$stmt_select = $conn->prepare("SELECT username, email, full_name, phone, address, created_at FROM users WHERE user_id = ?");
if (!$stmt_select) {
    die("Lỗi chuẩn bị (select): " . $conn->error);
}
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();
$user = $result_select->fetch_assoc();
$stmt_select->close();

if (!$user) {
    die("Không tìm thấy người dùng.");
}

// Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate cơ bản (bạn có thể thêm validate chi tiết hơn)
    if (empty($full_name)) {
        $error_message = "Tên đầy đủ không được để trống.";
    } elseif (empty($email)) {
        $error_message = "Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Địa chỉ email không hợp lệ.";
    } else {
        // Kiểm tra xem email mới có bị trùng với người dùng khác không (ngoại trừ chính người dùng này)
        $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        if (!$stmt_check_email) {
            $error_message = "Lỗi hệ thống (kiểm tra email).";
        } else {
            $stmt_check_email->bind_param("si", $email, $user_id);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();
            if ($result_check_email->num_rows > 0) {
                $error_message = "Địa chỉ email này đã được sử dụng bởi một tài khoản khác.";
            }
            $stmt_check_email->close();
        }

        if (empty($error_message)) { // Nếu không có lỗi validate email trùng
            $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            if (!$stmt_update) {
                $error_message = "Lỗi chuẩn bị (update): " . $conn->error;
            } else {
                $stmt_update->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
                if ($stmt_update->execute()) {
                    $message = "Cập nhật thông tin thành công!";
                    // Cập nhật lại thông tin trong $user để hiển thị trên form nếu người dùng không rời trang
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                    $user['address'] = $address;
                    // Nếu email được cập nhật, có thể bạn muốn cập nhật cả $_SESSION['email'] nếu có lưu
                } else {
                    $error_message = "Lỗi cập nhật thông tin: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        }
    }
}

// Lấy số lượng hàng trong giỏ (tương tự các trang khác)
$cart_item_count = 0;
$stmt_cart = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
if ($stmt_cart) {
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart_count = $stmt_cart->get_result();
    if ($row_cart_count = $result_cart_count->fetch_assoc()) {
        $cart_item_count = (int)($row_cart_count['total_items'] ?? 0);
    }
    $stmt_cart->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Thông Tin Cá Nhân - GoFood</title>
    <link rel="stylesheet" href="css.css"> 
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding-top: 70px;
        }
        .container {
            width: 80%;
            max-width: 700px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .container h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .submit-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            display: block;
            width: auto;
            margin: 0 auto; /* Căn giữa nút */
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        /* Navbar styles - consistent with user_profile.php */
        header {
            background-color: #ffcc00;
            padding: 10px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 90%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo img {
            height: 50px;
        }
        .nav-user-wrapper {
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex: 1; 
            margin-left: 30px;
        }
        nav ul.navbar {
            list-style: none; padding: 0; margin: 0; display: flex;
        }
        nav ul.navbar li {
            margin-left: 25px;
        }
        nav ul.navbar li a {
            text-decoration: none; color: #333; font-weight: bold; padding: 5px 10px; border-radius: 5px; transition: background-color 0.3s ease, color 0.3s ease;
        }
        nav ul.navbar li a:hover, nav ul.navbar li a.active {
            background-color: #28a745; color: white;
        }
        .user-actions {
            display: flex; align-items: center;
        }
        .user-actions .username-display, .user-actions .cart-button, .user-actions .logout-button {
            color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; margin-left: 15px; font-weight: bold; transition: background-color 0.3s ease; border: none; cursor: pointer; font-size: 0.9em;
        }
        .user-actions .username-display { background-color: #e65c00; }
        .user-actions .cart-button { background-color: #d32f2f; position: relative; }
        .user-actions .cart-button .cart-count {
            position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white;
        }
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
    <h1>Chỉnh Sửa Thông Tin Cá Nhân</h1>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="edit_profile.php" method="POST">
        <div class="form-group">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            <small>Tên đăng nhập không thể thay đổi.</small>
        </div>

        <div class="form-group">
            <label for="full_name">Tên đầy đủ:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Số điện thoại:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
        </div>

        <div class="form-group">
            <label for="address">Địa chỉ:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
        </div>
        
        <button type="submit" class="submit-btn">Lưu Thay Đổi</button>
    </form>
    <a href="user_profile.php" class="back-link">Quay lại hồ sơ</a>
</div>

<script>
    // To make the cart button in header work if you have a toggleCart function
    // Ensure cart_db.js or similar is loaded if toggleCart() is defined there.
    function toggleCart() {
        // Dummy function if not defined elsewhere, replace with actual mini-cart logic
        var cartModal = document.getElementById('cartModal'); // Assuming you have a cart modal with this ID
        if (cartModal) {
            cartModal.style.display = cartModal.style.display === 'none' || cartModal.style.display === '' ? 'block' : 'none';
        }
        // console.log('Toggle cart clicked');
    }
</script>

</body>
</html> 