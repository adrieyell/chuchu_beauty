document.addEventListener("DOMContentLoaded", () => {
  // DOM Elements
  const modal = document.getElementById("product-detail-modal");
  const closeBtn = modal ? modal.querySelector(".close-btn") : null;
  const detailView = document.getElementById("product-details-view");

  // --- Helper Functions ---

  // Function to visually update the cart counter (badge/icon)
  const updateCartCounter = (count) => {
    const cartIcon = document.querySelector(".fa-shopping-cart");
    // Implement code to update a cart badge here
    console.log("Cart updated. Total items:", count);
  };

  // Function to hide the modal
  const hideModal = () => {
    modal.style.display = "none";
    document.body.style.overflow = "auto"; // Re-enable background scrolling
  };

  // --- Main Logic: Show Product Details Modal ---

  // Function to fetch product details and show the modal (Attached to window for global access)
  window.showProductDetails = async (productId) => {
    try {
      // Fetch product details via AJAX
      const res = await fetch(`fetch_product_details.php?id=${productId}`);
      const product = await res.json();

      if (!product || product.error) {
        alert(product.error || "Failed to load product details.");
        return;
      }

      // Inject detailed content into the modal (using the structure from the prototype)
      detailView.innerHTML = `
                <div class="product-detail-layout">
                    <div class="detail-image">
                        <img src="${
                          product.image_url || "assets/images/placeholder.jpg"
                        }" alt="${product.name}">
                    </div>
                    <div class="detail-info">
                        <span class="detail-category">${
                          product.category || "NO CATEGORY"
                        }</span>
                        <h2>${product.name}</h2>
                        <p class="detail-price">₱${parseFloat(
                          product.price
                        ).toFixed(2)}</p>
                        <p class="rating">⭐️⭐️⭐️⭐️⭐️</p>
                        
                        <p class="product-description">${product.description.substring(
                          0,
                          200
                        )}...</p>
                        
                        <div class="quantity-control">
                            <label for="modal-quantity">Quantity</label>
                            <div class="qty-box">
                                <button class="qty-minus" data-action="minus">-</button>
                                <input type="number" id="modal-quantity" value="1" min="1" max="${
                                  product.stock_quantity
                                }">
                                <button class="qty-plus" data-action="plus">+</button>
                            </div>
                        </div>
                        
                        <button class="btn-pink add-to-cart-btn" data-product-id="${
                          product.product_id
                        }">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `;

      // SHOW THE MODAL
      modal.style.display = "flex";
      document.body.style.overflow = "hidden"; // Prevent background scrolling

      // Add event listeners for quantity control inside the modal
      const qtyInput = document.getElementById("modal-quantity");
      document.querySelectorAll(".qty-box button").forEach((button) => {
        button.onclick = () => {
          let currentQty = parseInt(qtyInput.value);
          if (button.dataset.action === "minus" && currentQty > 1) {
            qtyInput.value = currentQty - 1;
          } else if (
            button.dataset.action === "plus" &&
            currentQty < product.stock_quantity
          ) {
            qtyInput.value = currentQty + 1;
          }
        };
      });

      // Add event listener for the "Add to Cart" button
      document.querySelector(".add-to-cart-btn").onclick = function () {
        const id = this.dataset.productId;
        const qty = parseInt(document.getElementById("modal-quantity").value);
        addToCart(id, qty);
      };
    } catch (error) {
      console.error("Error fetching product details:", error);
      alert("An error occurred while loading product details.");
    }
  };

  // --- AJAX Function: Add to Cart ---
  const addToCart = async (productId, quantity) => {
    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("quantity", quantity);

    try {
      const response = await fetch("add_to_cart.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        alert(result.message);
        updateCartCounter(result.cart_count);
        hideModal(); // Use the centralised function
      } else {
        alert("Failed to add item: " + result.message);
      }
    } catch (error) {
      console.error("Error adding to cart:", error);
      alert("An error occurred. Please try again.");
    }
  };

  // --- MODAL CLOSE HANDLERS (Cleaned up) ---
  if (modal && closeBtn) {
    // Close on X button click
    closeBtn.onclick = hideModal;

    // Close when clicking outside the modal content
    window.onclick = (event) => {
      if (event.target == modal) {
        hideModal();
      }
    };
  }

  // --- Other Functions ---
  // Function to switch to the Checkout View
  window.showCheckoutView = () => {
    document.getElementById("shopping-cart-view").style.display = "none";
    document.getElementById("checkout-view").style.display = "block";
  };

  // Function to go back to Cart View (if needed)
  window.showCartView = () => {
    document.getElementById("shopping-cart-view").style.display = "block";
    document.getElementById("checkout-view").style.display = "none";
  };
});
