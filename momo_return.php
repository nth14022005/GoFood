<?php
session_start();
include 'db_connect.php';
// Nếu bạn lưu trữ key ở file config riêng thì include vào đây
// include 'momo_config.php';

$momo_secret_key = (getenv("GOFOOD_MOMO_SECRET_KEY") ?: ""); // THAY THẾ BẰNG SECRET KEY THẬT CỦA BẠN

// Lấy các tham số MoMo gửi về qua URL
$partnerCode = $_GET['partnerCode'] ?? null;
$orderId = $_GET['orderId'] ?? null;
$requestId = $_GET['requestId'] ?? null;
$amount = $_GET['amount'] ?? null;
$orderInfo = $_GET['orderInfo'] ?? null;
$orderType = $_GET['orderType'] ?? null;
$transId = $_GET['transId'] ?? null; // Mã giao dịch của MoMo
$resultCode = $_GET['resultCode'] ?? null; // Mã kết quả
$message = $_GET['message'] ?? null; // Thông báo từ MoMo
$payType = $_GET['payType'] ?? null;
$responseTime = $_GET['responseTime'] ?? null;
$extraData = $_GET['extraData'] ?? null;
$received_signature = $_GET['signature'] ?? null;

// Ghi log lại các tham số nhận được để debug
// $log_message = date('[Y-m-d H:i:s]') . " Return URL Received: " . json_encode($_GET) . "\n";
// file_put_contents('momo_return_log.txt', $log_message, FILE_APPEND);

// Xây dựng lại chuỗi để kiểm tra chữ ký (theo đúng thứ tự MoMo yêu cầu cho return URL)
// Ví dụ (CẦN KIỂM TRA LẠI VỚI TÀI LIỆU MOMO CHO RETURN URL):
// $rawHash = "partnerCode=" . $partnerCode .
//            "&accessKey=" . "YOUR_MOMO_ACCESS_KEY" . // AccessKey của bạn
//            "&requestId=" . $requestId .
//            "&amount=" . $amount .
//            "&orderId=" . $orderId .
//            "&orderInfo=" . $orderInfo .
//            "&orderType=" . $orderType .
//            "&transId=" . $transId .
//            "&message=" . $message .
//            "&localMessage=" . ($_GET['localMessage'] ?? '') .
//            "&responseTime=" . $responseTime .
//            "&errorCode=" . $resultCode .
//            "&payType=" . $payType;
//            // "&extraData=" . $extraData; // Nếu có

// $calculated_signature = hash_hmac("sha256", $rawHash, $momo_secret_key);

// ***** QUAN TRỌNG: BẠN CẦN TỰ XÂY DỰNG $rawHash VÀ $calculated_signature THEO ĐÚNG TÀI LIỆU RETURN URL CỦA MOMO *****

$signature_is_valid = false;
// TODO: Bỏ comment và hoàn thiện phần tạo $rawHash và kiểm tra chữ ký
/*
if ($received_signature == $calculated_signature) {
    $signature_is_valid = true;
} else {
    // Ghi log lỗi chữ ký không khớp
    file_put_contents('momo_return_log.txt', date('[Y-m-d H:i:s]') . " Return URL Signature Mismatch. Received: " . $received_signature . " | Calculated: " . $calculated_signature . " | RawHash: " . $rawHash . "\n", FILE_APPEND);
}
*/

// GIẢ LẬP CHỮ KÝ HỢP LỆ ĐỂ TEST (XÓA KHI CÓ CODE KIỂM TRA CHỮ KÝ THẬT)
if ($resultCode !== null) { // Chỉ cần có resultCode để test
    $signature_is_valid = true;
}


$page_title = "Kết quả Thanh toán";
$display_message = "";
$is_success = false;

if ($signature_is_valid) {
    if ($resultCode == 0) { // Giao dịch thành công
        $display_message = "Thanh toán đơn hàng #{$orderId} thành công! Cảm ơn bạn đã mua hàng.";
        $display_message .= "<br>Mã giao dịch MoMo: {$transId}";
        $is_success = true;
        // Tại đây, bạn có thể kiểm tra lại trạng thái đơn hàng trong DB (đã được cập nhật bởi IPN)
        // và hiển thị thông tin chi tiết hơn nếu muốn.
        // Không nên cập nhật DB ở đây, chỉ dựa vào IPN để cập nhật.
    } else {
        // Giao dịch thất bại hoặc bị hủy
        $display_message = "Thanh toán đơn hàng #{$orderId} thất bại.";
        $display_message .= "<br>Lỗi từ MoMo: " . htmlspecialchars($message) . " (Mã lỗi: {$resultCode})";
        $display_message .= "<br>Vui lòng thử lại hoặc chọn phương thức thanh toán khác.";
    }
} else {
    // Chữ ký không hợp lệ
    $display_message = "Lỗi xác thực thông tin thanh toán. Vui lòng liên hệ bộ phận hỗ trợ.";
    // Ghi log lỗi nghiêm trọng này
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - GoFood</title>
    <link rel="stylesheet" href="css.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        h1 {
            color: #333;
        }
        .message {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #ff6600;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-link:hover {
            background-color: #e65c00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $page_title; ?></h1>
        <div class="message <?php echo $is_success ? 'success' : 'error'; ?>">
            <?php echo $display_message; ?>
        </div>
        <a href="index.php" class="back-link">Quay về Trang Chủ</a>
        <?php if ($orderId && $is_success): ?>
             <!-- Bạn có thể thêm link xem chi tiết đơn hàng nếu có trang đó -->
             <!-- <a href="order_details.php?order_id=<?php echo htmlspecialchars($orderId); ?>" class="back-link" style="margin-left: 10px;">Xem đơn hàng</a> -->
        <?php endif; ?>
    </div>
</body>
</html> 