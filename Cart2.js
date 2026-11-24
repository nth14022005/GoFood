let cart = []; // Mảng lưu trữ giỏ hàng

// Hàm thêm món vào giỏ
function addToCart(name, price, imageUrl) { 
  if (isNaN(price) || price <= 0) {
    alert("Giá món không hợp lệ!");
    return;
  }

  let itemIndex = cart.findIndex(item => item.name === name);
  if (itemIndex !== -1) {
    cart[itemIndex].quantity += 1;
  } else {
    cart.push({ name, price: parseFloat(price), quantity: 1, imageUrl });
  }

  updateCart();
}

// Cập nhật giỏ hàng
function updateCart() {
  const cartList = document.getElementById("cartList");
  const cartCount = document.getElementById("cartCount");
  const totalPriceElem = document.getElementById("totalPrice");

  // Kiểm tra nếu các phần tử chưa tồn tại thì thoát
  if (!cartList || !cartCount || !totalPriceElem) return;

  cartList.innerHTML = "";
  let totalPrice = 0;

  cart.forEach((item, index) => {
    const li = document.createElement("li");

    const img = document.createElement("img");
    img.src = item.imageUrl;
    img.alt = item.name;
    img.style.width = "50px";
    img.style.height = "50px";
    img.style.objectFit = "cover";

    li.innerHTML = `${item.name} - ${item.quantity} x ${item.price.toFixed(0)}đ = ${(item.quantity * item.price).toFixed(0)}đ`;
    li.prepend(img);

    const removeBtn = document.createElement("button");
    removeBtn.textContent = "Xóa";
    removeBtn.className = "remove-btn";
    removeBtn.onclick = function () {
      cart.splice(index, 1);
      updateCart();
    };

    li.appendChild(removeBtn);
    cartList.appendChild(li);

    totalPrice += item.quantity * item.price;
  });

  cartCount.textContent = cart.length;
  totalPriceElem.textContent = totalPrice.toFixed(0) + "đ";
}