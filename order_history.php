<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Khách';

// Lấy danh sách đơn hàng của người dùng
$orders = [];
$stmt_orders = $conn->prepare("SELECT order_id, created_at, total, payment_method, payment_status, customer_name, customer_phone, customer_address FROM orders WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt_orders) {
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    while ($row = $result_orders->fetch_assoc()) {
        // Lấy chi tiết sản phẩm cho mỗi đơn hàng
        $order_items = [];
        $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price as item_price, p.name as product_name, p.image_url 
                                      FROM order_items oi 
                                      JOIN products p ON oi.product_id = p.product_id 
                                      WHERE oi.order_id = ?");
        if ($stmt_items) {
            $stmt_items->bind_param("i", $row['order_id']);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($item_row = $result_items->fetch_assoc()) {
                $order_items[] = $item_row;
            }
            $stmt_items->close();
        }
        $row['items'] = $order_items;
        $orders[] = $row;
    }
    $stmt_orders->close();
} else {
    // Xử lý lỗi nếu prepare statement thất bại
    error_log("Prepare failed for get orders: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Mua Hàng - GoFood</title>
    <link rel="stylesheet" href="css.css"> <!-- Link CSS chung nếu có -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #ff6600;
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin-left: 20px;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .order-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .order-history-table th, .order-history-table td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: left;
            font-size: 15px;
        }
        .order-history-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        .order-history-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .order-details {
            margin-top: 10px;
            padding-left: 20px;
        }
        .order-details p {
            margin: 5px 0;
            font-size: 14px;
        }
        .product-name-cell img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
            border-radius: 4px;
            vertical-align: middle;
        }
        .product-name-cell {
            display: flex;
            align-items: center;
        }
        .no-orders {
            text-align: center;
            font-size: 18px;
            color: #666;
            padding: 30px;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-pending { background-color: #ffc107; color: #333; }
        .badge-paid { background-color: #28a745; }
        .badge-cod { background-color: #17a2b8; }
        .badge-failed { background-color: #dc3545; }
        .badge-processing { background-color: #007bff; }

        .address-details {
            font-size: 0.9em;
            color: #555;
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">GoFood</a>
    <div>
        <span>Xin chào, <?php echo htmlspecialchars($username); ?>!</span>
        <a href="index.php">Trang Chủ</a>
        <a href="cart_page.php">Giỏ Hàng</a>
        <a href="logout.php">Đăng Xuất</a>
    </div>
</div>

<div class="container">
    <h1>Lịch Sử Mua Hàng</h1>
    <?php if (!empty($orders)): ?>
        <table class="order-history-table">
            <thead>
                <tr>
                    <th>Mã ĐH</th>
                    <th>Ngày Đặt</th>
                    <th>Thông Tin Giao Hàng</th>
                    <th>Sản phẩm</th>
                    <th>Tổng Tiền</th>
                    <th>Thanh Toán</th>
                    <th>Trạng Thái TT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($order['created_at'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                            <span class="address-details"><?php echo htmlspecialchars($order['customer_address']); ?></span>
                        </td>
                        <td>
                            <?php if (!empty($order['items'])): ?>
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dashed #eee;">
                                            <div class="product-name-cell">
                                                <img src="<?php echo htmlspecialchars(!empty($item['image_url']) ? $item['image_url'] : 'images/default-product.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php echo htmlspecialchars($item['product_name']); ?> 
                                            </div>
                                            x <?php echo $item['quantity']; ?> - <?php echo number_format($item['item_price'], 0, ',', '.'); ?>đ
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Không có thông tin sản phẩm.</p>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</strong></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td>
                            <?php 
                            $status_text = htmlspecialchars($order['payment_status']);
                            $status_class = 'badge-secondary'; // Mặc định
                            if (strpos(strtolower($status_text), 'chờ thanh toán momo') !== false || strpos(strtolower($status_text), 'chờ thanh toán') !== false ) {
                                $status_class = 'badge-pending';
                            } elseif (strpos(strtolower($status_text), 'đã thanh toán') !== false) {
                                $status_class = 'badge-paid';
                            } elseif (strpos(strtolower($status_text), 'thất bại') !== false) {
                                $status_class = 'badge-failed';
                            } elseif (strpos(strtolower($status_text), 'chờ xác nhận') !== false && strtolower($order['payment_method']) == 'cod') {
                                $status_class = 'badge-cod';
                            } elseif (strpos(strtolower($status_text), 'đang xử lý') !== false || strpos(strtolower($status_text), 'chờ xác nhận') !== false) {
                                $status_class = 'badge-processing';
                            }
                            echo "<span class='badge {$status_class}'>{$status_text}</span>";
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-orders">Bạn chưa có đơn hàng nào.</p>
    <?php endif; ?>
</div>

</body>
</html> 