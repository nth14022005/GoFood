<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Khách';

if (!$user_id) {
    header('Location: login.php'); // Chuyển hướng đến trang đăng nhập nếu chưa đăng nhập
    exit;
}

// Lấy thông tin giỏ hàng từ DB để hiển thị tóm tắt
$cart_items = [];
$total_price = 0;

$stmt_get_cart = $conn->prepare("SELECT p.name, p.price, ci.quantity, p.image_url FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.user_id = ?");
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
    // Xử lý lỗi nếu không thể prepare statement
    error_log("Prepare failed for get cart items: " . $conn->error);
    // Có thể hiển thị thông báo lỗi cho người dùng nếu cần
}

if (empty($cart_items)) {
    // Nếu giỏ hàng trống, có thể chuyển hướng về trang chủ hoặc hiển thị thông báo
    // header('Location: index.php');
    // exit;
    // Hoặc đơn giản là không cho phép đặt hàng (nút đặt hàng bị vô hiệu hóa ở client-side hoặc thông báo)
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - GoFood</title>
    <link rel="stylesheet" href="css.css"> <!-- Liên kết CSS chung nếu có -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Căn các mục từ trên xuống */
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 700px;
            margin-top: 20px;
        }
        h1, h2 {
            color: #333;
            text-align: center;
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
        .form-group input[type='text'],
        .form-group input[type='tel'] {
            width: calc(100% - 22px); /* Adjust width to account for padding and border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .cart-summary {
            margin-bottom: 30px;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .cart-summary h2 {
            margin-top: 0;
            color: #ff6600;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .cart-summary ul {
            list-style: none;
            padding: 0;
        }
        .cart-summary li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            font-size: 15px;
        }
        .cart-summary li:last-child {
            border-bottom: none;
        }
        .cart-summary .total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 15px;
            color: #d9534f;
            text-align: right;
        }
        .btn-submit {
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            display: block;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-name {
            flex-basis: 60%;
        }
        .product-quantity {
            flex-basis: 15%;
            text-align: center;
        }
        .product-price {
            flex-basis: 25%;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Xác nhận Thanh Toán</h1>

    <div id="checkoutMessage" class="message" style="display: none;"></div>

    <?php if (!empty($cart_items)): ?>
    <div class="cart-summary">
        <h2>Tóm tắt đơn hàng</h2>
        <ul>
            <?php foreach ($cart_items as $item): ?>
                <li>
                    <span class="product-name"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                    <span class="product-price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="total">
            Tổng cộng: <?php echo number_format($total_price, 0, ',', '.'); ?>đ
        </div>
    </div>

    <form id="checkoutForm">
        <h2>Thông tin giao hàng</h2>
        <div class="form-group">
            <label for="customer_name">Họ và tên:</label>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_phone">Số điện thoại:</label>
            <input type="tel" id="customer_phone" name="customer_phone" required>
        </div>
        <div class="form-group">
            <label for="customer_address">Địa chỉ nhận hàng:</label>
            <input type="text" id="customer_address" name="customer_address" required>
        </div>

        <h2>Chọn hình thức thanh toán</h2>
        <div class="form-group payment-methods">
            <div>
                <input type="radio" id="payment_cod" name="payment_method" value="COD" checked>
                <label for="payment_cod">Thanh toán khi nhận hàng (COD)</label>
            </div>
            <div>
                <input type="radio" id="payment_bank" name="payment_method" value="Bank Transfer">
                <label for="payment_bank">Chuyển khoản ngân hàng</label>
            </div>
            <div>
                <input type="radio" id="payment_momo_qr" name="payment_method" value="Momo QR">
                <label for="payment_momo_qr">Thanh toán qua MoMo QR</label>
            </div>
        </div>

        <div id="bankInfo" class="form-group" style="display: none; background-color: #eef; padding: 15px; border-radius: 5px;">
            <h4>Thông tin chuyển khoản:</h4>
            <p><strong>Ngân hàng:</strong> MB Bank</p>
            <p><strong>Số tài khoản:</strong> 0374639283</p>
            <p><strong>Chủ tài khoản:</strong> Gofood</p>
            <p><strong>Nội dung chuyển khoản:</strong> Tên + SĐT <span id="bankOrderInfo"></span> - Gofood xin cảm ơn!</p>
            <p><small>Lưu ý: Đơn hàng của bạn sẽ được xử lý sau khi chúng tôi xác nhận đã nhận được thanh toán.</small></p>
        </div>

        <div id="momoQrInfo" class="form-group" style="display: none; text-align: center; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
            <h4>Quét mã MoMo QR để thanh toán</h4>
            <img id="momoQrImage" src="" alt="MoMo QR Code" style="width: 250px; height: 250px; margin: 10px auto; display: block; border: 1px solid #eee;">
            <p><small>Vui lòng hoàn tất thanh toán trong 5 phút. Đơn hàng sẽ được xử lý sau khi chúng tôi nhận được xác nhận thanh toán từ MoMo.</small></p>
            <button type="button" id="checkMomoPaymentBtn" style="margin-top:15px; padding: 10px 20px; background-color: #ae2070; color:white; border:none; border-radius:5px; cursor:pointer;">Tôi đã thanh toán</button>
        </div>

        <button type="submit" class="btn-submit" id="submitOrderBtn">Xác nhận Đặt Hàng</button>
    </form>
    <?php else: ?>
        <p style="text-align:center; font-size: 18px; color: #555;">Giỏ hàng của bạn đang trống. Vui lòng <a href="index.php">quay lại trang chủ</a> để chọn món.</p>
    <?php endif; ?>

</div>

<script>
    const checkoutForm = document.getElementById('checkoutForm');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    const checkoutMessage = document.getElementById('checkoutMessage');
    const paymentBankRadio = document.getElementById('payment_bank');
    const paymentCodRadio = document.getElementById('payment_cod');
    const bankInfoDiv = document.getElementById('bankInfo');
    const paymentMomoQrRadio = document.getElementById('payment_momo_qr');
    const momoQrInfoDiv = document.getElementById('momoQrInfo');
    const bankOrderInfoSpan = document.getElementById('bankOrderInfo');
    const momoOrderInfoSpan = document.getElementById('momoOrderInfo');

    function updatePaymentDisplay() {
        if (paymentBankRadio && paymentBankRadio.checked) {
            bankInfoDiv.style.display = 'block';
        } else {
            bankInfoDiv.style.display = 'none';
        }

        if (paymentMomoQrRadio && paymentMomoQrRadio.checked) {
            momoQrInfoDiv.style.display = 'block';
        } else {
            momoQrInfoDiv.style.display = 'none';
        }
    }

    if (paymentBankRadio) {
        paymentBankRadio.addEventListener('change', updatePaymentDisplay);
    }
    if (paymentCodRadio) {
        paymentCodRadio.addEventListener('change', updatePaymentDisplay);
    }
    if (paymentMomoQrRadio) {
        paymentMomoQrRadio.addEventListener('change', updatePaymentDisplay);
    }

    updatePaymentDisplay();

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            submitOrderBtn.disabled = true;
            submitOrderBtn.textContent = 'Đang xử lý...';
            checkoutMessage.style.display = 'none';
            checkoutMessage.className = 'message';

            const formData = {
                customer_name: document.getElementById('customer_name').value,
                customer_phone: document.getElementById('customer_phone').value,
                customer_address: document.getElementById('customer_address').value,
                payment_method: document.querySelector('input[name="payment_method"]:checked').value
            };

            try {
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    let messageText = result.message;
                    if (result.payment_method === 'Momo QR' && result.qr_code_url) {
                        messageText = result.message + ' Vui lòng quét mã QR để hoàn tất thanh toán.';
                    } else if (result.payment_method === 'Bank Transfer') {
                        messageText = result.message + ' Vui lòng chuyển khoản theo thông tin bên dưới.';
                    } else {
                        // COD or other direct success
                        messageText = result.message + ' Cảm ơn bạn đã đặt hàng!';
                    }

                    checkoutMessage.innerHTML = `
                        <p>${messageText}</p>
                        <div style="margin-top: 15px;">
                            <a href="index.php" style="padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">Trở về Trang Chủ</a>
                            <a href="order_history.php" style="padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Lịch sử Mua Hàng</a>
                        </div>
                    `;

                    checkoutMessage.classList.add('success');
                    checkoutMessage.style.display = 'block';
                    checkoutForm.style.display = 'none';
                    
                    if (result.order_id) {
                        if (bankOrderInfoSpan) bankOrderInfoSpan.textContent = result.order_id;
                        if (momoOrderInfoSpan) momoOrderInfoSpan.textContent = result.order_id;
                    }

                    if (result.payment_method === 'Momo QR' && result.qr_code_url) {
                        document.getElementById('momoQrImage').src = result.qr_code_url;
                        momoQrInfoDiv.style.display = 'block';
                        bankInfoDiv.style.display = 'none';
                        submitOrderBtn.style.display = 'none';

                        const checkMomoPaymentBtn = document.getElementById('checkMomoPaymentBtn');
                        if(checkMomoPaymentBtn) {
                            checkMomoPaymentBtn.onclick = function() {
                                checkoutMessage.textContent = 'Đang kiểm tra thanh toán MoMo...';
                                alert('Chức năng kiểm tra thanh toán MoMo tự động sẽ được phát triển sau. Hiện tại, chúng tôi sẽ xử lý đơn hàng của bạn sau khi nhận được thông báo từ MoMo. Cảm ơn!');
                            };
                        }
                    } else if (result.payment_method === 'Bank Transfer') {
                        bankInfoDiv.style.display = 'block';
                        momoQrInfoDiv.style.display = 'none';
                        submitOrderBtn.style.display = 'none';
                    } else {
                        // COD success, buttons already added, hide other payment info elements if any
                        bankInfoDiv.style.display = 'none';
                        momoQrInfoDiv.style.display = 'none';
                        submitOrderBtn.style.display = 'none'; // Ensure main submit button is hidden
                    }
                } else {
                    checkoutMessage.textContent = 'Lỗi: ' + (result.message || 'Không thể đặt hàng. Vui lòng thử lại.');
                    checkoutMessage.classList.add('error');
                    checkoutMessage.style.display = 'block';
                    submitOrderBtn.disabled = false;
                    submitOrderBtn.textContent = 'Xác nhận Đặt Hàng';
                }
            } catch (error) {
                console.error('Error during checkout:', error);
                checkoutMessage.textContent = 'Đã có lỗi xảy ra trong quá trình đặt hàng. Vui lòng thử lại.';
                checkoutMessage.classList.add('error');
                checkoutMessage.style.display = 'block';
                submitOrderBtn.disabled = false;
                submitOrderBtn.textContent = 'Xác nhận Đặt Hàng';
            }
        });
    }
    if (empty($cart_items)) {
        if(submitOrderBtn) submitOrderBtn.disabled = true;
        const paymentMethodsDiv = document.querySelector('.payment-methods');
        if(paymentMethodsDiv) paymentMethodsDiv.style.display = 'none';
    }
</script>

</body>
</html> 