<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Khách';

// Lấy thông tin giỏ hàng từ DB
$cart_items = [];
$total_price = 0;

$stmt_get_cart = $conn->prepare("SELECT p.product_id, p.name, p.price, p.image_url, ci.quantity 
                                FROM cart_items ci 
                                JOIN products p ON ci.product_id = p.product_id 
                                WHERE ci.user_id = ?");
if ($stmt_get_cart) {
    $stmt_get_cart->bind_param("i", $user_id);
    $stmt_get_cart->execute();
    $result_cart = $stmt_get_cart->get_result();
    while ($row = $result_cart->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }
    $stmt_get_cart->close();
} else {
    error_log("Prepare failed for get cart items on cart_page: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - GoFood</title>
    <link rel="stylesheet" href="css.css">
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
            max-width: 1000px;
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
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .cart-table th, .cart-table td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }
        .cart-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .product-info {
            display: flex;
            align-items: center;
        }
        .quantity-input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        .btn-update-quantity, .btn-remove-item {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 5px;
        }
        .btn-update-quantity {
            background-color: #007bff;
            color: white;
        }
        .btn-remove-item {
            background-color: #dc3545;
            color: white;
        }
        .cart-summary {
            text-align: right;
            margin-bottom: 30px;
        }
        .cart-summary h3 {
            font-size: 22px;
            color: #d9534f;
            margin-bottom: 20px;
        }
        .btn-checkout {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }
        .btn-checkout:hover {
            background-color: #218838;
        }
        .empty-cart {
            text-align: center;
            font-size: 18px;
            color: #666;
            padding: 40px 20px;
        }
        .empty-cart a {
            color: #ff6600;
            text-decoration: none;
            font-weight: bold;
        }
        .empty-cart a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">GoFood</a>
    <div>
        <span>Xin chào, <?php echo htmlspecialchars($username); ?>!</span>
        <a href="index.php">Trang Chủ</a>
        <a href="order_history.php">Lịch Sử Mua Hàng</a>
        <a href="logout.php">Đăng Xuất</a>
    </div>
</div>

<div class="container">
    <h1>Giỏ Hàng Của Bạn</h1>

    <?php if (!empty($cart_items)): ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th>Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr data-product-id="<?php echo $item['product_id']; ?>">
                    <td>
                        <div class="product-info">
                            <img src="<?php echo htmlspecialchars(!empty($item['image_url']) ? $item['image_url'] : 'images/default-product.png'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                    </td>
                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                    <td>
                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" data-product-id="<?php echo $item['product_id']; ?>">
                        <!-- Nút cập nhật sẽ được thêm chức năng bằng JS sau -->
                    </td>
                    <td class="item-total-price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</td>
                    <td>
                        <button class="btn-remove-item" data-product-id="<?php echo $item['product_id']; ?>">Xóa</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <h3>Tổng cộng: <span id="cart-total-price"><?php echo number_format($total_price, 0, ',', '.'); ?>đ</span></h3>
            <a href="checkout_page.php" class="btn-checkout">Tiến Hành Thanh Toán</a>
            <div style="margin-top: 15px;">
                <a href="order_history.php" class="btn-secondary-action" style="padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;">Xem Lịch Sử Mua Hàng</a>
            </div>
        </div>

    <?php else: ?>
        <div class="empty-cart">
            <p>Giỏ hàng của bạn hiện đang trống.</p>
            <p><a href="index.php">Tiếp tục mua sắm</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
// Xử lý sự kiện cho các nút cập nhật số lượng và xóa sản phẩm sẽ dùng cart_db.js
// Vì trang này là trang đầy đủ (không phải popup), chúng ta cần đảm bảo cart_db.js được tải
// và các hàm của nó có thể được gọi để cập nhật giỏ hàng trên server.

// Chức năng cơ bản để cập nhật tổng tiền và thành tiền khi số lượng thay đổi (client-side)
document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const newQuantity = parseInt(this.value);
        const row = this.closest('tr');
        const price = parseFloat(row.querySelector('td:nth-child(2)').textContent.replace(/[^0-9.-]+/g,"").replace('.', '')); // Lấy giá từ cột đơn giá
        
        if (newQuantity >= 1) {
            const itemTotalPriceElement = row.querySelector('.item-total-price');
            itemTotalPriceElement.textContent = (price * newQuantity).toLocaleString('vi-VN') + 'đ';
            updateCartTotal();

            // Gọi hàm cập nhật số lượng lên server (AJAX qua cart_handler.php)
            // Bạn cần có hàm này trong cart_db.js hoặc viết ở đây
            // Ví dụ: updateCartItemQuantityOnServer(productId, newQuantity);
            // Hiện tại chỉ log ra console
            console.log(`Cần gọi AJAX để cập nhật product_id: ${productId} với số lượng mới: ${newQuantity}`);
            // Giả sử bạn có hàm updateCartItemQuantity từ cart_db.js hoặc cart_handler.php
             fetch('cart_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update&product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Số lượng đã được cập nhật trên server.');
                    // Có thể cập nhật lại số lượng trên header nếu có
                     if (typeof updateUserCartCount === 'function') { // Kiểm tra nếu hàm updateUserCartCount tồn tại
                        updateUserCartCount(data.total_items_in_cart);
                    }
                } else {
                    console.error('Lỗi cập nhật số lượng trên server:', data.message);
                    // Có thể rollback số lượng trên giao diện nếu cập nhật server lỗi
                }
            });

        } else {
            this.value = 1; // Không cho số lượng nhỏ hơn 1
        }
    });
});

function updateCartTotal() {
    let total = 0;
    document.querySelectorAll('.cart-table tbody tr').forEach(row => {
        const itemTotalText = row.querySelector('.item-total-price').textContent;
        total += parseFloat(itemTotalText.replace(/[^0-9.-]+/g,"").replace('.', ''));
    });
    document.getElementById('cart-total-price').textContent = total.toLocaleString('vi-VN') + 'đ';
}


// Xử lý xóa sản phẩm
document.querySelectorAll('.btn-remove-item').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.productId;
        if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            // Gọi hàm xóa sản phẩm trên server (AJAX qua cart_handler.php)
            // Ví dụ: removeCartItemOnServer(productId);
            console.log(`Cần gọi AJAX để xóa product_id: ${productId}`);
            fetch('cart_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    console.log('Sản phẩm đã được xóa trên server.');
                    this.closest('tr').remove(); // Xóa hàng khỏi bảng
                    updateCartTotal(); // Cập nhật lại tổng tiền
                    if (document.querySelectorAll('.cart-table tbody tr').length === 0) {
                        // Nếu không còn sản phẩm nào, hiển thị thông báo giỏ hàng trống
                        const container = document.querySelector('.container');
                        container.innerHTML = `
                            <h1>Giỏ Hàng Của Bạn</h1>
                            <div class="empty-cart">
                                <p>Giỏ hàng của bạn hiện đang trống.</p>
                                <p><a href="index.php">Tiếp tục mua sắm</a></p>
                            </div>`;
                    }
                     if (typeof updateUserCartCount === 'function') { // Kiểm tra nếu hàm updateUserCartCount tồn tại
                        updateUserCartCount(data.total_items_in_cart);
                    }
                } else {
                    alert('Lỗi khi xóa sản phẩm: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi fetch khi xóa sản phẩm:', error);
                alert('Có lỗi xảy ra, không thể xóa sản phẩm.');
            });
        }
    });
});

// Hàm này cần được định nghĩa ở đâu đó global hoặc trong cart_db.js và được include
// Ví dụ một hàm updateUserCartCount (cần đảm bảo nó tồn tại và hoạt động đúng)
// function updateUserCartCount(count) {
//    const cartCountElement = document.getElementById('cartItemCount'); // Giả sử bạn có element này trên header
//    if (cartCountElement) {
//        cartCountElement.textContent = count;
//    }
// }


</script>

</body>
</html> 