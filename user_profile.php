<?php
session_start();
include 'db_connect.php'; 

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Chuyển hướng đến trang đăng nhập nếu chưa đăng nhập
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Khách'; // Lấy username từ session, nếu không có thì mặc định là 'Khách'

// Lấy thông tin chi tiết của người dùng từ CSDL
$stmt = $conn->prepare("SELECT username, email, full_name, phone, address, created_at FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Lỗi chuẩn bị câu lệnh: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Xử lý trường hợp không tìm thấy user (dù đã đăng nhập), có thể là lỗi dữ liệu
    die("Không tìm thấy thông tin người dùng.");
}
$stmt->close();

// Lấy số lượng hàng trong giỏ (tương tự index.php)
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

$conn->close(); // Đóng kết nối sau khi đã lấy hết dữ liệu cần thiết
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Cá Nhân - <?php echo htmlspecialchars($user['username']); ?> - GoFood</title>
    <link rel="stylesheet" href="css.css"> <!-- Link đến file CSS chung -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding-top: 70px; /*Để navbar không che mất nội dung */
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 30px auto;
            background-color: #fff; /* Reverted to white */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .profile-header img.avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #28a745; /* Màu xanh lá cây của GoFood */
        }
        .profile-header h1 {
            margin: 0;
            color: #333;
            font-size: 2em;
        }
        .profile-header p {
            color: #777;
            font-size: 1.1em;
        }
        .profile-details h2 {
            background-color: #ffcc00; /* GoFood yellow for heading */
            color: #333; /* Dark text for contrast */
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 0; /* Adjust if needed */
            margin-bottom: 20px; /* Space below heading */
        }
        .profile-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .profile-details th, .profile-details td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .profile-details th {
            background-color: #f9f9f9;
            width: 30%;
            font-weight: bold;
            color: #555;
        }
        .profile-details td {
            color: #333;
        }
        .profile-details tr:nth-child(even) {
            background-color: #f9f9f9; /* Light gray for even rows */
        }

        .profile-details tr:hover {
            background-color: #f1f1f1; /* Slightly darker gray on hover for any row */
        }
        .profile-actions {
            text-align: center;
            margin-top: 30px;
        }
        .profile-actions .btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .profile-actions .btn:hover {
            background-color: #218838;
        }
        .profile-actions .btn-secondary {
            background-color: #6c757d;
        }
        .profile-actions .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Styles for navbar, copied and adapted from index.php if needed */
        header {
            background-color: #ffcc00; /* Màu vàng chủ đạo của GoFood */
            padding: 10px 5%; /* 5% padding lề trái phải */
            display: flex;
            justify-content: space-between; /* Căn logo và nav sang hai bên */
            align-items: center;
            position: fixed; /* Giữ header cố định khi cuộn */
            top: 0;
            left: 0;
            width: 90%; /* header chiếm 90% chiều rộng, còn 10% cho padding trái phải */
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .logo img {
            height: 50px; /* Chiều cao logo */
        }

        nav ul.navbar {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }

        nav ul.navbar li {
            margin-left: 25px; /* Khoảng cách giữa các mục nav */
        }

        nav ul.navbar li a {
            text-decoration: none;
            color: #333; /* Màu chữ cho nav items */
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        nav ul.navbar li a:hover,
        nav ul.navbar li a.active {
            background-color: #28a745; /* Màu nền khi hover/active */
            color: white; /* Màu chữ khi hover/active */
        }
        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-actions .username-display,
        .user-actions .cart-button,
        .user-actions .logout-button {
            background-color: #e65c00; /* Màu cam cho các nút */
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            margin-left: 15px; /* Khoảng cách giữa các nút */
            font-weight: bold;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
        }
        .user-actions .username-display {
             background-color: #e65c00; /* Màu cam */
        }
        .user-actions .cart-button {
            background-color: #d32f2f; /* Màu đỏ cho giỏ hàng */
            position: relative; /* Để định vị số lượng */
        }
         .user-actions .cart-button .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7em;
            border: 1px solid white;
        }

        .user-actions .logout-button {
            background-color: #555; /* Màu xám cho đăng xuất */
        }

        .user-actions .username-display:hover,
        .user-actions .cart-button:hover,
        .user-actions .logout-button:hover {
            opacity: 0.9;
        }

    </style>
</head>
<body>

<header>
    <div class="logo">
        <a href="index.php"><img src="image/Go Food.png" alt="Go Food Logo"></a>
    </div>
    <div class="nav-user-wrapper" style="display: flex; align-items: center; justify-content: space-between; flex: 1; margin-left: 30px;"> 
        <nav>
            <ul class="navbar">
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="khuyenmai.html">Khuyến mãi</a></li>
                <!-- Giả sử có các trang này -->
                <li><a href="order_history.php">Lịch sử mua hàng</a></li>
                <li><a href="contact.php">Hỗ trợ</a></li>
            </ul>
        </nav>
        <div class="user-actions">
            <?php if ($username !== 'Khách'): ?>
                <a href="user_profile.php" class="username-display"><?php echo "Xin chào, " . htmlspecialchars($username); ?></a>
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
                <a href="login.php" class="logout-button" style="text-decoration: none; color: white;">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <div class="profile-header">
        <!-- Bạn có thể thêm avatar nếu có cột avatar_url trong bảng users -->
        <img src="image/Go Food.png" alt="Avatar" class="avatar"> <!-- Ảnh đại diện mặc định -->
        <h1><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <div class="profile-details">
        <h2>Thông tin chi tiết</h2>
        <table>
            <tr>
                <th>Tên đăng nhập:</th>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
            </tr>
            <tr>
                <th>Tên đầy đủ:</th>
                <td><?php echo htmlspecialchars($user['full_name'] ?: 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <th>Số điện thoại:</th>
                <td><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <th>Địa chỉ:</th>
                <td><?php echo htmlspecialchars($user['address'] ?: 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <th>Ngày tham gia:</th>
                <td><?php echo date("d/m/Y H:i", strtotime($user['created_at'])); ?></td>
            </tr>
        </table>
    </div>

    <div class="profile-actions">
        <button class="btn" onclick="window.location.href='edit_profile.php'">Chỉnh sửa thông tin</button>
        <button class="btn btn-secondary" onclick="window.location.href='change_password.php'">Đổi mật khẩu</button>
    </div>
</div>

</body>
</html> 