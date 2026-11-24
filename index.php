<?php
session_start();
include 'db_connect.php';

$username = $_SESSION['username'] ?? null;
$user_id = $_SESSION['user_id'] ?? null; // Lấy user_id từ session

$cart_item_count = 0; // Đổi tên biến để rõ ràng hơn
if ($user_id) { // Chỉ truy vấn nếu user_id tồn tại (người dùng đã đăng nhập)
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result_cart_count = $stmt->get_result(); // Đổi tên biến result để tránh xung đột
        if ($row_cart_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_cart_count['total_items'] ?? 0);
        }
        $stmt->close();
    } else {
        // Ghi lại lỗi nếu prepare statement thất bại, ví dụ: error_log("Prepare failed: " . $conn->error);
    }
}

$sql = "SELECT p.product_id AS id, p.name, p.price, p.image_url, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY c.name, p.name";

$result = $conn->query($sql);
if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}

// KHÔNG đóng kết nối $conn ở đây nếu bạn còn dùng nó ở dưới (ví dụ: nếu có code PHP khác sau danh sách sản phẩm)
// Nếu không, bạn có thể đóng nó sau khi vòng lặp sản phẩm kết thúc.

?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Go Food</title>
  <link rel="stylesheet" href="index.css">
  <style>
    /* CSS thêm vào để hỗ trợ phần tìm kiếm */
    .menu-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-bar {
      display: flex;
      border: 1px solid #ccc;
      border-radius: 20px;
      padding: 5px 10px;
      background-color: #fff;
    }

    .search-bar input {
      border: none;
      outline: none;
      padding: 5px;
    }

    .items {
  display: grid;
  grid-template-columns: repeat(5, 1fr); /* 5 món 1 hàng */
  gap: 20px;
  margin-top: 20px;
  max-width: 1100px; /* Đặt chiều rộng tối đa */
  margin-left: auto;
  margin-right: auto; /* Căn giữa */
  padding: 0 10px;
}

.item {
  background: #fff;
  padding: 10px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  text-align: center;
}

.item img {
  width: 100%;
  height: 130px;
  object-fit: cover;
  border-radius: 8px;
}

    .hidden {
      display: none;
    }
  </style>

  
</head>
<body>
  
  <header>
    <div class="logo">
      <img src="image/Go Food.png" alt="Go Food Logo">
    </div>
    <div class="nav-user-wrapper" style="display: flex; align-items: center; justify-content: space-between; flex: 1; margin-left: 30px;"> 
        <nav>
          <ul class="navbar">
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="khuyenmai.html">Khuyến mãi</a></li>
            <li><a href="#chinh-sach">Chính sách</a></li>
            <li><a href="#contact">Hỗ trợ</a></li>
        </ul>
        </nav>

        <div class="user-actions">
            <?php if ($username): ?>
                <a href="user_profile.php" class="username-display" style="text-decoration: none; color: white; background-color: #e65c00; padding: 8px 15px; border-radius: 20px; font-weight: bold; margin-left: 15px; font-size: 0.9em;"><?php echo "Xin chào, " . htmlspecialchars($username); ?></a>
                <button onclick="toggleCart()" class="cart-button" style="background-color: #d32f2f; color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; margin-left: 15px; font-weight: bold; position: relative; font-size: 0.9em; border: none; cursor: pointer;">
                    🛒 Giỏ hàng
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-count" id="cartCount" style="position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white;"><?php echo $cart_item_count; ?></span>
                    <?php else: ?>
                        <span class="cart-count" id="cartCount" style="position: absolute; top: -8px; right: -8px; background-color: #ffea00; color: #d32f2f; border-radius: 50%; padding: 2px 6px; font-size: 0.7em; border: 1px solid white;">0</span>
                    <?php endif; ?>
                </button>
                <a href="logout.php" class="logout-button" style="background-color: #555; color: white; padding: 8px 15px; border-radius: 20px; text-decoration: none; margin-left: 15px; font-weight: bold; font-size: 0.9em;">Đăng xuất</a>
            <?php else: ?>
                <style>
  .login-button {
    display: inline-block;
    background: #ff7315;
    color: white;
    padding: 15px 30px;
    border-radius: 25px;
    text-decoration: none;
    margin-left: 15px;
    font-weight: bold;
    font-size: 0.9em;
    border: 2px solid #ff7315;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    transition: background 0.3s, color 0.3s, transform 0.2s;
  }

  .login-button:hover {
    background: white;
    color: #ff7315;
    transform: translateY(-2px);
  }
</style>

<a href="login.php" class="login-button">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
  </header>

<!-- Danh sách phân loại món ăn -->
<div class="food-marquee-container">
  <div class="food-marquee-track">
    <img src="https://media.istockphoto.com/id/1461350378/vi/vec-to/b%E1%BB%99-bi%E1%BB%83u-t%C6%B0%E1%BB%A3ng-th%E1%BB%A9c-%C4%83n-nhanh.jpg?s=612x612&w=0&k=20&c=HQt1Lnj6pdf8oiqRuC_qAilLUqsQpeZLiSZBhQbOSHA=" alt="Phở bò">
    <img src="https://images.vexels.com/media/users/3/239354/raw/2dfd63916acf020483e322a4e18965a1-fast-food-illustration-pack.jpg" alt="Bún chả">
    <img src="https://bamahas.com/wp-content/uploads/2018/09/04.jpg" alt="Cơm gà">
    <img src="https://cdn.hercampus.com/SH6M70M3/as/8ktfmvqxpm8h7xtmgs2p39v/fastfoodmealdeals_1?width=398&height=256&fit=crop&auto=webp&dpr=4" alt="Cơm gà">
    <img src="https://vietnamteachingjobs.com/wp-content/uploads/2022/04/special-offers-for-fast-food-in-vietnam.jpg" alt="Cơm gà">
    <img src="https://vietnamteachingjobs.com/wp-content/uploads/2022/04/jolibee-fast-food-in-vietnam.jpg" alt="Cơm gà">
    <img src="https://xyz.com.vn/wp-content/uploads/2019/05/Fastfood-0-e1559113906872.jpg" alt="Cơm gà">
    <img src="https://images.theconversation.com/files/410720/original/file-20210712-46002-1ku5one.jpg?ixlib=rb-4.1.0&rect=0%2C71%2C994%2C497&q=45&auto=format&w=1356&h=668&fit=crop" alt="Cơm gà">
    <img src="https://media.istockphoto.com/id/952043566/vi/vec-to/%C4%91%E1%BA%B7t-bi%E1%BB%83u-t%C6%B0%E1%BB%A3ng-th%E1%BB%A9c-%C4%83n-nhanh-c%E1%BB%91c-cola-hamburger-ch%C3%A2n-g%C3%A0-r%C3%A1n-pizza.jpg?s=612x612&w=0&k=20&c=UxF3cE-ENlR1HjY8bqrNU3YZyoKvdkCjOLiwXkBZKxk=" alt="Cơm gà">

    <!-- Lặp lần 2 (để tạo vòng lặp mượt) -->
    <img src="https://media.istockphoto.com/id/1461350378/vi/vec-to/b%E1%BB%99-bi%E1%BB%83u-t%C6%B0%E1%BB%A3ng-th%E1%BB%A9c-%C4%83n-nhanh.jpg?s=612x612&w=0&k=20&c=HQt1Lnj6pdf8oiqRuC_qAilLUqsQpeZLiSZBhQbOSHA=" alt="Phở bò">
    <img src="https://images.vexels.com/media/users/3/239354/raw/2dfd63916acf020483e322a4e18965a1-fast-food-illustration-pack.jpg" alt="Bún chả">
    <img src="https://bamahas.com/wp-content/uploads/2018/09/04.jpg" alt="Cơm gà">
    <img src="https://cdn.hercampus.com/SH6M70M3/as/8ktfmvqxpm8h7xtmgs2p39v/fastfoodmealdeals_1?width=398&height=256&fit=crop&auto=webp&dpr=4" alt="Cơm gà">
    <img src="https://vietnamteachingjobs.com/wp-content/uploads/2022/04/special-offers-for-fast-food-in-vietnam.jpg" alt="Cơm gà">
    <img src="https://vietnamteachingjobs.com/wp-content/uploads/2022/04/jolibee-fast-food-in-vietnam.jpg" alt="Cơm gà">
    <img src="https://xyz.com.vn/wp-content/uploads/2019/05/Fastfood-0-e1559113906872.jpg" alt="Cơm gà">
    <img src="https://images.theconversation.com/files/410720/original/file-20210712-46002-1ku5one.jpg?ixlib=rb-4.1.0&rect=0%2C71%2C994%2C497&q=45&auto=format&w=1356&h=668&fit=crop" alt="Cơm gà">
    <img src="https://media.istockphoto.com/id/952043566/vi/vec-to/%C4%91%E1%BA%B7t-bi%E1%BB%83u-t%C6%B0%E1%BB%A3ng-th%E1%BB%A9c-%C4%83n-nhanh-c%E1%BB%91c-cola-hamburger-ch%C3%A2n-g%C3%A0-r%C3%A1n-pizza.jpg?s=612x612&w=0&k=20&c=UxF3cE-ENlR1HjY8bqrNU3YZyoKvdkCjOLiwXkBZKxk=" alt="Cơm gà">
  </div>
</div>

<div class="food-categories">
  <span data-category="Tất cả">Tất cả</span>
  <span data-category="Hamburger">Hamburger</span>
  <span data-category="Gà Rán">Gà Rán</span>
  <span data-category="Pizza">Pizza</span>
  <span data-category="Mì Ý">Mì Ý</span>
  <span data-category="Đồ uống">Đồ uống</span>
  <span data-category="Thức ăn nhẹ">Thức ăn nhẹ</span>
  <span data-category="Tráng miệng">Tráng miệng</span>
  <span data-category="Combo">Combo</span>
</div>
 <div class="search-bar">
  🔍 <input type="text" id="searchInput" placeholder="Tìm món ăn..." onkeyup="searchFood()">
</div>
</div>

<div class="items" id="foodList">
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
      <div class="item" data-category="<?php echo htmlspecialchars($row['category_name'] ?? 'Chưa phân loại'); ?>">
        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
        <p><?php echo number_format($row['price'], 0, ',', '.'); ?>đ</p>
        <button class="add-btn" onclick="addToCart(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', <?php echo $row['price']; ?>, '<?php echo htmlspecialchars(addslashes($row['image_url'])); ?>')">+ Thêm giỏ hàng</button>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align: center; width: 100%;">Không có sản phẩm nào để hiển thị.</p>
  <?php endif; ?>
  <?php 
    // Đóng kết nối $conn sau khi đã sử dụng xong $result
    // if ($conn) { // Kiểm tra nếu $conn còn tồn tại (chưa bị đóng ở nơi khác)
    //    $conn->close(); // Tạm thời comment lại nếu bạn có thể cần $conn ở cuối file cho việc khác.
    // }               // Tuy nhiên, với cấu trúc hiện tại, đây là nơi tốt để đóng.
  ?>
</div>
  </section>

  <div class="main-content">
    <!-- Danh sách món ăn -->
    <div class="product-list">
      <!-- Các món ăn như Mì Ý, Pizza, Gà rán, v.v. -->
    </div>
    <div class="popular-dishes">
      <h2>🍽️ Món Ăn Hot Nhất Hôm Nay</h2>
<div class="dish-slider">
  <div class="dish">
    <img src="https://static.kfcvietnam.com.vn/images/items/lg/Burger-Flava.jpg?v=L7DyN4" alt="Burger Gà Quay Flava" />
    <span class="led-text">Burger Gà Quay Flava</span>
  </div>
  <div class="dish">
    <img src="https://static.kfcvietnam.com.vn/images/items/lg/2-Fried-Chicken.jpg?v=LWkqBL" alt="Gà rán giòn" />
    <span class="led-text">Gà Rán Giòn</span>
  </div>
  <div class="dish">
    <img src="https://www.pizzaexpress.vn/wp-content/uploads/2020/11/ceac24f9e927f4b6256611c9c8228c76.jpg" alt="Pizza xúc xích" />
    <span class="led-text">Pizza Xúc Xích</span>
  </div>
</div>
</div>
<marquee style="background:rgb(253, 73, 23); color: white; padding: 13px; font-size: 18px;">
  🎉 Miễn phí ship đơn hàng từ 49K | 🍟 Tặng khoai tây chiên cho combo Gà Rán | 🥤 Nhận Trà Đào Cam Sả miễn phí trong hôm nay!
</marquee>   

<div style="background: rgb(255, 255, 255); padding: 40px 0; text-align: center;">
  <div style="
    max-width: 900px;
    margin: auto;
    background: #fff7ec;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    border: 2px solid #e65c00;
  ">
    <h2 style="color: rgb(246, 104, 9); margin-bottom: 30px;">🤔 Vì Sao Chọn Go Food?</h2>
    <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;">
      <div style="width: 200px;">
        <img src="https://cdn-icons-png.flaticon.com/128/1046/1046784.png" width="60">
        <p><strong>Giao Nhanh</strong><br>Chỉ 15 phút tận tay bạn</p>
      </div>
      <div style="width: 200px;">
        <img src="https://cdn-icons-png.flaticon.com/128/3075/3075977.png" width="60">
        <p><strong>Giá Hợp Lý</strong><br>Combo chỉ từ 85K</p>
      </div>
      <div style="width: 200px;">
        <img src="https://cdn-icons-png.flaticon.com/128/685/685352.png" width="60">
        <p><strong>Đảm Bảo Vệ Sinh</strong><br>100% kiểm định thực phẩm</p>
      </div>
    </div>
  </div>
</div>


<div style="background:rgb(255, 255, 255); padding: 40px 0; text-align: center;">
  <h2 style="color: #e65c00; margin-bottom: 20px;">🌟 Khách Hàng Nói Gì?</h2>
  <p style="font-size: 16px; color: #333; margin-bottom: 30px;">
    Hàng ngàn lượt đánh giá tích cực từ khách hàng thân thiết ❤️
  </p>
  <a href="danhgia.html" style="
      display: inline-block;
      padding: 12px 24px;
      background: linear-gradient(45deg, #ff6600, #ff8533);
      color: white;
      text-decoration: none;
      border-radius: 30px;
      font-weight: bold;
      font-size: 16px;
      transition: background 0.3s ease;">
    Xem Tất Cả Đánh Giá →
  </a>
</div>

 <section id="contact" class="contact">
  <div class="contact-grid">
    <!-- Gộp Hotline và Email vào 1 cột -->
    <div class="info">
  
  <h2 class="title">Liên hệ</h2>
  <hr class="separator">
    📞 Hotline: <strong>0909 554 226</strong><br>
    ✉️ Email: <strong>contact@example.com</strong><br>
    📍 Địa chỉ: <strong>255 Nguyễn Văn Cừ Nối Dài,               An Bình - TP.CầnThơ</strong></p>
    </div>
    

 <div class="service-description">
  <h2 class="title">Chính Sách</h2>
  <section id="chinh-sach"></section>
  <hr class="separator">
  <div><a href="Chinhsachbaomat.html"><strong>          •   Chính Sách Bảo Mật</strong></a></div>
  <div><a href="Chinhsachgiaohang.html"><strong>          •   Chính Sách Giao Hàng</strong></a></div>
  <div><a href="Chinhsachthanhtoan.html"><strong>          •   Chính Sách Thanh Toán</strong></a></div>
  <div><a href="Chinhsachdoitra.html"><strong>          •   Chính Sách Đổi Trả</strong></a></div>
</div>

<style>
  .service-description a {
    text-decoration: none;
    color: rgb(255, 255, 255); /* Có thể đổi màu tùy ý */
    text-align: left;
  }

  .service-description a:hover {
    color: rgb(255, 0, 0); /* Khi rê chuột vào sẽ đổi màu, tùy chỉnh theo ý bạn */
  }
</style>

  <div class="service-description">
  <h2 class="title">🍔Go Food</h2>
  <hr class="separator">
  <p class="info"><strong>Giao hàng mọi nơi trong thời gian nhanh nhất, nhận hàng nhanh chóng. Hình ảnh chụp thật 100%. Đảm bảo sản phẩm đúng như hình đăng tải. Liên hệ tư vấn nhanh chóng khi khách hàng đặt sản phẩm.</strong></p>
</div>
</section>

<style>
@import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap');

.contact {
  text-align: center;
  padding: 50px 20px;
  background: #ffb74d;
  font-family: 'Quicksand', sans-serif;
}

.contact h2 {
  font-size: 25px;
  color: #ff0000;
  margin-bottom: 20px;
}

.contact .info {
  font-size: 20px;
  color: #333;
  margin: 10px 0;
  text-align: left;
}

.contact .info strong {
  color: #ffffff;
}
</style>
  
  <style>
  .contact {
    text-align: center;
    padding: 30px 10px;
  }
  
  .decor-line {
    position: relative;
    margin: 20px auto;
    width: 80%;
    height: 1px;
    background-color: #f58e07;
  }
  
  .decor-line span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffb74d;
    padding: 0 10px;
    font-size: 24px;
  }
  </style>
  
</body>
</html>

<!-- Giỏ hàng danh sách -->
<div id="cartModal" style="position: fixed; top: 20%; right: 20px; background: white; 
                           border: 1px solid #ddd; padding: 10px; width: 400px; display: none; 
                           box-shadow: 0 0 10px rgba(0,0,0,0.1); z-index: 999;">
  <h3>Giỏ hàng</h3>
  <ul id="cartList"></ul>
<span>Tổng tiền: </span>
<span id="totalPrice">0đ</span>
  <button onclick="clearCart()" style="background-color: #ff5722; color: white; padding: 8px; width: 100%; 
                                        border: none; border-radius: 4px; cursor: pointer;">
    Xóa giỏ hàng
    <button onclick="checkoutCart()" style="background-color: #4caf50; color: white; padding: 8px; width: 100%; 
    border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">
    Thanh toán
  </button>
  
</div>

<title>Go Food Chatbot</title>
  <style>
    .chat-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: linear-gradient(45deg, #ff6600, #ff8533);
      color: white;
      padding: 12px 20px;
      border-radius: 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      font-weight: bold;
      font-size: 18px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .chat-btn:hover {
      transform: scale(1.05);
      background: linear-gradient(45deg, #e65c00, #ff9933);
    }
    .chat-box {
      width: 370px;
      border-radius: 12px;
      border: 1px solid #ccc;
      position: fixed;
      bottom: 80px;
      right: 20px;
      background: #fff;
      display: none;
      flex-direction: column;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      font-family: 'Segoe UI', sans-serif;
    }
    .chat-box.show {
      display: flex;
    }
    .chat-header {
      background: linear-gradient(90deg, #ff6600, #ff9933);
      color: white;
      padding: 12px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: bold;
      font-size: 16px;
    }
    .chat-header .close-btn {
      background: transparent;
      border: none;
      font-size: 20px;
      color: white;
      cursor: pointer;
    }
    .chat-body {
      padding: 12px;
      height: 200px;
      overflow-y: auto;
      background: #fafafa;
      font-size: 18px;
    }
    .chat-input {
      display: flex;
      border-top: 1px solid #eee;
    }
    .chat-input input {
      flex: 1;
      padding: 10px;
      border: none;
      outline: none;
      font-size: 14px;
    }
    .send-btn {
      padding: 10px 15px;
      background: #ff6600;
      color: white;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .send-btn:hover {
      background: #e65c00;
    }
    
    .navbar a {
  text-decoration: none;
  color:
  font-weight: bold;
  font-size: 22px;    
  padding: 100px 2px; 
  border: 2px solid transparent; 
  border-radius: 12px;
  position: relative;
  transition: all 0.3s ease;
}
  </style>

</head>
<body>

<!-- Nút bật chat -->
<div class="chat-btn">Chat</div>

<!-- Hộp chat -->
<div class="chat-box">
  <div class="chat-header">
    <span>Hỗ trợ</span>
    <button class="close-btn">×</button>
  </div>
  <div class="chat-body">
    <p><strong>Go Food:</strong> Chào bạn! Bạn cần hỗ trợ gì?</p>
  </div>
  <div class="chat-input">
    <input type="text" placeholder="Nhập tin nhắn..." />
    <button class="send-btn">Gửi</button>
  </div>
</div>

<script>
  const chatBtn = document.querySelector('.chat-btn');
  const chatBox = document.querySelector('.chat-box');
  const closeBtn = document.querySelector('.close-btn');
  const sendBtn = document.querySelector('.send-btn');
  const inputField = document.querySelector('.chat-input input');
  const chatBody = document.querySelector('.chat-body');

  // Hiện hộp chat khi bấm nút Chat
  chatBtn.addEventListener('click', () => {
    chatBox.classList.add('show');
  });

  // Ẩn hộp chat khi bấm nút X
  closeBtn.addEventListener('click', () => {
    chatBox.classList.remove('show');
  });

// Hàm gửi tin nhắn
function sendMessage() {
  const message = inputField.value.trim();
  if (message === '') return;

  // Hiển thị tin nhắn người dùng
  chatBody.innerHTML += `<p><strong>Bạn:</strong> ${message}</p>`;

  // Chuyển tin nhắn thành chữ thường (vẫn giữ dấu)
  const msg = message.toLowerCase();

  // Bot trả lời theo từ khóa
  let reply = 'Xin lỗi, mình chưa hiểu ý bạn.';

  if (msg.includes('menu')) {
    reply = 'Bạn có thể xem thực đơn tại mục "Menu" nha!';
  } else if (msg.includes('khi nào shop mở cửa')) {
    reply = 'FastFood mở cửa từ 8h đến 22h mỗi ngày.';
  } else if (msg.includes('bên shop có giao hàng không') || msg.includes('ship')) {
    reply = 'Dạ có! Bên mình có giao hàng tận nơi.';
  } else if (msg.includes('gợi ý cho tôi món ngon') || msg.includes('gợi ý')) {
    reply = 'Mình sẽ gợi ý cho bạn một số món ngon như :<br> (1) Cơm Gà Xối Mỡ<br>  (2) Burger Gà Giòn Sốt BBQ<br> (3) Gà Popcorn Cay Giòn';
  } else if (msg.includes('hello') || msg.includes('xin chào')) {
    reply = 'Chào bạn! Mình có thể giúp gì cho bạn hôm nay?';
  } else if (msg.includes('cảm ơn') || msg.includes('thank')) {
    reply = 'Rất vui được hỗ trợ bạn!';
  }

  // Hiển thị tin nhắn trả lời
  chatBody.innerHTML += `<p><strong>Go Food: </strong> ${reply}</p>`;

  // Cuộn xuống cuối khung chat
  chatBody.scrollTop = chatBody.scrollHeight;

  // Xóa nội dung input
  inputField.value = '';
}

  // Bấm nút Gửi
  sendBtn.addEventListener('click', sendMessage);

  // Bấm Enter
  inputField.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') sendMessage();
  });
</script>

</body>
</html>
<script src="cart_db.js"></script>
<script src="Chatbox.js"></script>
<script src="Searchfood.js"></script>
