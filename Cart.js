function addToCart(productId, productName) {
  fetch('add_to_cart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Đã thêm "' + productName + '" vào giỏ hàng!');
      updateCartCount(); // Cập nhật số lượng hiển thị giỏ hàng (nếu có)
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể thêm sản phẩm'));
    }
  })
  .catch(error => {
    alert('Lỗi mạng hoặc server: ' + error);
  });
}