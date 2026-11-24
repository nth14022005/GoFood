 function searchFood() {
      const input = document.getElementById("searchInput").value.toLowerCase();
      const items = document.querySelectorAll("#foodList .item");

      items.forEach(item => {
        const name = item.querySelector("h3").textContent.toLowerCase();
        item.classList.toggle("hidden", !name.includes(input));
      });
    }