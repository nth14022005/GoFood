<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Bạn cần đăng nhập để xem đơn hàng.");
}

// Lấy đơn hàng mới nhất
$sql_order = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $user_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
    die("Bạn chưa có đơn hàng nào.");
}

$order = $result_order->fetch_assoc();
$order_id = $order['id'];

// Lấy chi tiết đơn hàng
$sql_items = "SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

// Xử lý nút thanh toán (giả lập)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $payment_method = $order['payment_method'];
    if ($payment_method === 'cod') {
        // Cập nhật trạng thái đơn thành "Đang giao hàng"
        $stmt_update = $conn->prepare("UPDATE orders SET status = 'Đang giao hàng' WHERE id = ?");
        $stmt_update->bind_param("i", $order_id);
        $stmt_update->execute();
        $message = "Cảm ơn bạn! Đơn hàng đang được xử lý và sẽ giao đến bạn sớm.";
    } elseif ($payment_method === 'bank_transfer') {
        $message = "Vui lòng chuyển khoản theo hướng dẫn để đơn hàng được xử lý.";
    } else {
        $message = "Chức năng thanh toán online đang phát triển.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Chi tiết đơn hàng và thanh toán</title>
</head>
<body>
    <h1>Đơn hàng của bạn (#<?php echo $order_id; ?>)</h1>

    <?php if (!empty($message)): ?>
        <p style="color:green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <p><strong>Ngày đặt:</strong> <?php echo $order['created_at']; ?></p>
    <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_price']); ?> VND</p>
    <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
    <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status'] ?? 'Chưa cập nhật'); ?></p>

    <h2>Chi tiết món hàng</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Tên món</th>
            <th>Số lượng</th>
            <th>Giá</th>
            <th>Thành tiền</th>
        </tr>
        <?php while ($item = $result_items->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td><?php echo number_format($item['price']); ?> VND</td>
            <td><?php echo number_format($item['price'] * $item['quantity']); ?> VND</td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>Thông tin khách hàng</h2>
    <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>

    <form method="POST">
        <button type="submit" name="pay">Xác nhận thanh toán</button>
    </form>

</body>
</html>