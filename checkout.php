<?php
session_start();
include 'db_connect.php';
// include 'momo_handler.php'; // Giả sử bạn có file xử lý MoMo riêng

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']));
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Kiểm tra nếu json_decode thất bại với input không rỗng
if (json_last_error() !== JSON_ERROR_NONE && !empty($json)) {
    die(json_encode(['success' => false, 'message' => 'Lỗi định dạng dữ liệu gửi lên.']));
}

$customer_name = $data['customer_name'] ?? '';
$customer_phone = $data['customer_phone'] ?? '';
$customer_address = $data['customer_address'] ?? '';
$payment_method = $data['payment_method'] ?? 'COD'; // Mặc định là COD nếu không có

if (empty($customer_name) || empty($customer_phone) || empty($customer_address) || empty($payment_method)) {
    die(json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng hoặc hình thức thanh toán']));
}

// Lấy giỏ hàng từ DB
$sql = "SELECT p.product_id as id, p.name as product_name, p.price, ci.quantity FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.user_id = ?";
$stmt_cart_items = $conn->prepare($sql);
if ($stmt_cart_items === false) {
    die(json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi lấy thông tin giỏ hàng.']));
}
$stmt_cart_items->bind_param("i", $user_id);
$stmt_cart_items->execute();
$result_cart = $stmt_cart_items->get_result();

$items = [];
$total = 0;
$order_description = "Thanh toán đơn hàng GoFood cho ".$username; // $username từ session

while ($row = $result_cart->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
    // $order_description .= $row['product_name'] . " x" . $row['quantity'] . ", ";
}
$stmt_cart_items->close();

// $order_description = rtrim($order_description, ", ");


if (empty($items)) {
    die(json_encode(['success' => false, 'message' => 'Giỏ hàng trống']));
}

// Bắt đầu giao dịch
$conn->begin_transaction();
try {
    // Thêm đơn hàng
    // Cập nhật payment_status dựa trên payment_method
    $payment_status = 'Chờ xác nhận'; // Mặc định cho COD
    if ($payment_method === 'Bank Transfer') {
        $payment_status = 'Chờ thanh toán';
    } elseif ($payment_method === 'Momo QR') {
        $payment_status = 'Chờ thanh toán MoMo';
    }

    $stmt_order = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt_order === false) {
        $conn->rollback();
        die(json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi tạo đơn hàng (prepare): ' . $conn->error]));
    }
    $stmt_order->bind_param("isssdss", $user_id, $customer_name, $customer_phone, $customer_address, $total, $payment_method, $payment_status);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    if (!$order_id) {
        $conn->rollback();
        die(json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi tạo đơn hàng (get order_id).']));
    }
    $stmt_order->close();

    // Thêm từng sản phẩm
    $stmt_order_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if ($stmt_order_items === false) {
        $conn->rollback();
        die(json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi thêm sản phẩm vào đơn hàng.']));
    }
    foreach ($items as $item) {
        $stmt_order_items->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt_order_items->execute();
    }
    $stmt_order_items->close();

    // Xử lý thanh toán MoMo nếu được chọn
    if ($payment_method === 'Momo QR') {
        // **PHẦN CODE TÍCH HỢP MOMO THỰC TẾ SẼ Ở ĐÂY**
        // Giả sử bạn có hàm handleMomoPayment() trả về một mảng chứa thông tin MoMo
        // $momoResponse = handleMomoPayment($order_id, $total, "Thanh toán đơn hàng #".$order_id, "YOUR_RETURN_URL", "YOUR_NOTIFY_URL");

        // GIẢ LẬP PHẢN HỒI TỪ MOMO API ĐỂ TEST
        // Trong thực tế, bạn sẽ gọi API MoMo và nhận payUrl (hoặc qrCodeUrl) từ họ
        $momo_partner_code = (getenv("GOFOOD_MOMO_PARTNER_CODE") ?: ""); // Lấy từ cấu hình
        $momo_access_key = (getenv("GOFOOD_MOMO_ACCESS_KEY") ?: ""); // Lấy từ cấu hình
        $momo_secret_key = (getenv("GOFOOD_MOMO_SECRET_KEY") ?: ""); // Lấy từ cấu hình
        $momo_endpoint = "https://test-payment.momo.vn/v2/gateway/api/create"; // Endpoint test của MoMo

        $requestId = (string) time(); // Unique request ID
        $amount = (string) $total;
        $orderInfo = "Thanh toán đơn hàng GoFood #" . $order_id;
        $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/gofood/momo_return.php"; // Trang của bạn MoMo redirect về
        $ipnUrl = "http://" . $_SERVER['HTTP_HOST'] . "/gofood/momo_ipn.php"; // Trang của bạn MoMo gửi IPN
        $requestType = "captureWallet"; // Với QR là 'captureWallet' hoặc 'payWithATM' nếu dùng QR cho ATM qua MoMo
        $extraData = ""; // base64 encoded json string if needed

        // Chuỗi raw hash: partnerCode=$partnerCode&accessKey=$accessKey&requestId=$requestId&amount=$amount&orderId=$orderId&orderInfo=$orderInfo&returnUrl=$returnUrl&notifyUrl=$notifyUrl&extraData=$extraData
        $rawHash = "accessKey=" . $momo_access_key .
                   "&amount=" . $amount .
                   "&extraData=" . $extraData .
                   "&ipnUrl=" . $ipnUrl .
                   "&orderId=" . $order_id . // Nên dùng mã đơn hàng của bạn
                   "&orderInfo=" . $orderInfo .
                   "&partnerCode=" . $momo_partner_code .
                   "&redirectUrl=" . $redirectUrl .
                   "&requestId=" . $requestId .
                   "&requestType=" . $requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $momo_secret_key);

        $data_momo = array(
            'partnerCode' => $momo_partner_code,
            'accessKey' => $momo_access_key,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => (string) $order_id, // MoMo yêu cầu orderId là string và duy nhất
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => $requestType,
            'extraData' => $extraData,
            'lang' => 'vi',
            'signature' => $signature,
        );

        // Giả lập gọi API MoMo, trong thực tế bạn sẽ dùng cURL
        // Đây là phần bạn cần thay thế bằng SDK hoặc cURL thật sự
        // $ch = curl_init($momo_endpoint);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_momo));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $momo_api_response_json = curl_exec($ch);
        // curl_close($ch);
        // $momo_api_response = json_decode($momo_api_response_json, true);

        // GIẢ LẬP phản hồi thành công từ MoMo API với payUrl (chứa QR)
        $momo_api_response = [
            'resultCode' => 0, // 0 là thành công
            // 'payUrl' => 'https://test-payment.momo.vn/gw_payment/qr?partnerCode=MOMOBKUN20180529&accessKey=iPXneGmrflscF1WT&requestId=1678886400&amount=50000&orderId=ORDER123&orderInfo=Thanh+toan+don+hang&returnUrl=https%3A%2F%2Fexample.com%2Fmomo_return&notifyUrl=https%3A%2F%2Fexample.com%2Fmomo_notify&requestType=captureMoMoWallet&signature=xxxxxxx', // URL QR thật sẽ khác
            'qrCodeUrl' => 'https://test-payment.momo.vn/pay/store/MOMOT5L520230829-17032309105101?d=JTdCJTIycGFydG5lckNvZGUlMjIlM0ElMjJNT01PVDVMNTIwMjMwODI5JTIyJTJDJTIyYWNjZXNzS2V5JTIyJTNBJTIyaW50ZXJuYWxBY2Nlc3NLZXklMjIlMkMlMjJyZXF1ZXN0SWQlMjIlM0ElMjIxNzAzMjMwOTEwNTEwMSUyMiUyQyUyMmFtb3VudCUyMiUzQSUyMjEwMDAwJTIyJTJDJTIyb3JkZXJJZCUyMiUzQSUyMjE3MDMyMzA5MTA1MTAxJTIyJTJDJTIyb3JkZXJJbmZvJTIyJTNBJTIyVGhhbmglMjB0byVDMyVBMW4lMjAlQzQlOTElQzYlQTFuJTIwaCVDMyVBMG5nJTIyJTJDJTIycmV0dXJuVXJsJTIyJTNBJTIyaHR0cHMlM0ElMkYlMkZtaW5oLm1vYmlsZXRvcHVwLnZuJTJGbW9tbyUyRnJldHVybiUyMiUyQyUyMm5vdGlmeVVybCUyMiUzQSUyMmh0dHBzJTNBJTJGJTJGbWluaC5tb2JpbGV0b3B1dC52biUyRm1vbW8lMkZub3RpZnklMjIlMkMlMjJyZXF1ZXN0VHlwZSUyMiUzQSUyMnBheVdpdGhXQVAlMjIlMkMlMjJzaWduYXR1cmUlMjIlM0ElMjJiOWMzOTRmYTVjYmU3ODhhM2Y3YzM3NzkxMzFmZjI5YTcwNzI0YjgxZTBkYTQ1NzMwY2VmZjJmYmMyNmZhMjgxJTIyJTJDJTIybGFuZyUyMiUzQSUyMnZpJTIyJTJDJTIyZXh0cmFEYXRhJTIyJTNBJTIyJTIyJTdE', // Ví dụ một QR Code URL từ MoMo (URL này thường là payUrl với type QR)
            'message' => 'Thành công'
        ];
        // END GIẢ LẬP

        if (isset($momo_api_response['resultCode']) && $momo_api_response['resultCode'] == 0 && (isset($momo_api_response['payUrl']) || isset($momo_api_response['qrCodeUrl']))) {
            // Không xóa giỏ hàng ngay, chỉ xóa sau khi MoMo xác nhận thanh toán qua IPN
            $conn->commit(); // Commit transaction để lưu đơn hàng
            
            // Ưu tiên qrCodeUrl nếu có, nếu không thì payUrl (payUrl thường chứa QR hoặc dẫn đến trang chọn phương thức của MoMo)
            $qr_url_to_send = $momo_api_response['qrCodeUrl'] ?? $momo_api_response['payUrl'];

            echo json_encode([
                'success' => true, 
                'message' => 'Đã tạo đơn hàng. Vui lòng quét mã MoMo để thanh toán.', 
                'order_id' => $order_id, 
                'payment_method' => $payment_method,
                'qr_code_url' => $qr_url_to_send // Gửi URL QR code về cho client
            ]);
        } else {
            $conn->rollback();
            $error_message = $momo_api_response['message'] ?? 'Lỗi khi khởi tạo thanh toán MoMo.';
            die(json_encode(['success' => false, 'message' => 'Lỗi MoMo: ' . $error_message, 'momo_response' => $momo_api_response]));
        }
    } else { // COD hoặc Bank Transfer
        // Xóa giỏ hàng
        $stmt_delete_cart = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        if ($stmt_delete_cart === false) {
            $conn->rollback();
            die(json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi dọn dẹp giỏ hàng.']));
        }
        $stmt_delete_cart->bind_param("i", $user_id);
        $stmt_delete_cart->execute();
        $stmt_delete_cart->close();

        $conn->commit();
        $success_message = 'Đặt hàng thành công!';
        if ($payment_method === 'Bank Transfer') {
            $success_message = 'Đơn hàng của bạn đã được ghi nhận. Vui lòng chuyển khoản theo thông tin để chúng tôi xử lý.';
        }
        echo json_encode(['success' => true, 'message' => $success_message, 'order_id' => $order_id, 'payment_method' => $payment_method]);
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Checkout Error: " . $e->getMessage() . " for user_id: " . $user_id . " with data: " . json_encode($data));
    echo json_encode(['success' => false, 'message' => 'Lỗi khi đặt hàng: ' . $e->getMessage()]);
}
?>