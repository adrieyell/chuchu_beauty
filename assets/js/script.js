document.addEventListener("DOMContentLoaded", () => {
  // Global variables
  let selectedVariantId = null;

  // --- Helper Functions ---

  // Function to visually update the cart counter (badge/icon)
  const updateCartCounter = (count) => {
    const cartBadge = document.querySelector(".cart-count");
    if (cartBadge) {
      cartBadge.textContent = count;
    }
    console.log("Cart updated. Total items:", count);
  };

  // Function to hide the modal
  const hideModal = () => {
    const modal = document.getElementById("product-detail-modal");
    if (modal) {
      modal.style.display = "none";
      document.body.style.overflow = "auto";
      selectedVariantId = null;
    }
  };

  // --- Quantity Controls ---
  window.increaseQuantity = () => {
    const qtyInput = document.getElementById("quantity");
    if (qtyInput) {
      const maxStock = parseInt(qtyInput.getAttribute("max"));
      let currentQty = parseInt(qtyInput.value);

      if (currentQty < maxStock) {
        qtyInput.value = currentQty + 1;
      }
    }
  };

  window.decreaseQuantity = () => {
    const qtyInput = document.getElementById("quantity");
    if (qtyInput) {
      let currentQty = parseInt(qtyInput.value);
      if (currentQty > 1) {
        qtyInput.value = currentQty - 1;
      }
    }
  };

  // --- Variant Selection Logic ---
  window.selectShade = (button) => {
    document.querySelectorAll(".shade-option").forEach((opt) => {
      opt.classList.remove("active");
    });

    button.classList.add("active");
    selectedVariantId = button.dataset.variantId;

    const newMaxStock = parseInt(button.dataset.stock);
    const qtyInput = document.getElementById("quantity");
    const stockInfoText = document.getElementById("stock-info-text");

    if (qtyInput) {
      qtyInput.setAttribute("max", newMaxStock);
      let currentQty = parseInt(qtyInput.value);
      if (currentQty > newMaxStock) {
        qtyInput.value = 1;
      }
    }

    if (stockInfoText) {
      stockInfoText.innerHTML = `<i class="fas fa-box"></i> ${
        newMaxStock > 0 ? `${newMaxStock} items in stock` : "Out of stock"
      }`;
    }

    const errorMsg = document.getElementById("shade-error");
    if (errorMsg) {
      errorMsg.style.display = "none";
    }
  };

  // --- Add to Cart ---
  window.addToCart = (productId) => {
    const quantityInput = document.getElementById("quantity");
    if (!quantityInput) {
      console.error("Quantity input not found");
      return;
    }

    const quantity = parseInt(quantityInput.value);
    const maxStock = parseInt(quantityInput.getAttribute("max"));

    // Check if shade selector exists in the DOM
    const shadeSelector = document.querySelector(".shade-selector");
    const hasVariants = shadeSelector !== null;

    console.log(
      "Add to cart - Has variants:",
      hasVariants,
      "Selected variant:",
      selectedVariantId
    );

    // Only require variant selection if product has variants AND shade options are available
    if (hasVariants) {
      const shadeOptions = document.querySelectorAll(".shade-option");
      if (shadeOptions.length > 0 && !selectedVariantId) {
        const errorMsg = document.getElementById("shade-error");
        if (errorMsg) {
          errorMsg.style.display = "block";
        }
        alert("Please select a shade before adding to cart");
        return;
      }
    }

    if (quantity < 1 || quantity > maxStock) {
      alert(`Invalid quantity. Maximum stock is ${maxStock}.`);
      return;
    }

    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("quantity", quantity);

    // Only send variant_id if a variant was selected
    if (selectedVariantId) {
      formData.append("variant_id", selectedVariantId);
    }

    fetch("add_to_cart.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(data.message);
          updateCartCounter(data.cart_count);
          hideModal();
        } else {
          alert(data.message || "Failed to add to cart");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  };

  // --- Show Product Details Modal ---
  window.showProductDetails = (productId) => {
    console.log("showProductDetails called with ID:", productId);

    const modal = document.getElementById("product-detail-modal");
    const detailsContainer = document.getElementById("product-details-view");

    if (!modal || !detailsContainer) {
      console.error("Modal elements not found.");
      alert("Error: Modal elements not found on page");
      return;
    }

    // Show modal with loading state
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
    detailsContainer.innerHTML =
      '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2em; color: var(--pink-dark);"></i><p>Loading...</p></div>';

    // Fetch product details
    const fetchUrl = `fetch_product_details.php?id=${productId}`;
    console.log("Fetching from:", fetchUrl);

    fetch(fetchUrl)
      .then((response) => {
        console.log("Response status:", response.status);
        return response.json();
      })
      .then((data) => {
        console.log("Parsed data:", data);

        if (data.error) {
          detailsContainer.innerHTML = `<p style="color: red; text-align: center; padding: 40px;">${data.error}</p>`;
          return;
        }

        // Determine initial stock and variant
        selectedVariantId = null;
        let initialMaxStock = data.stock_quantity
          ? parseInt(data.stock_quantity)
          : 0;
        let initialVariantId = null;

        const hasVariants = data.variants && data.variants.length > 0;

        console.log("Product has variants:", hasVariants);
        console.log("Variants data:", data.variants);

        // If product has variants, use first variant's stock
        if (hasVariants) {
          const firstVariant = data.variants[0];
          initialMaxStock = parseInt(firstVariant.stock_quantity);
          initialVariantId = firstVariant.variant_id;
          selectedVariantId = initialVariantId;
        }

        // Build shade selector HTML - only if variants exist
        let shadeHTML = "";
        if (hasVariants) {
          shadeHTML = `
            <div class="shade-selector">
              <label style="display: block; margin-bottom: 10px; font-weight: bold; color: var(--pink-dark);">
                Select Shade
              </label>
              <div class="shade-options">
                ${data.variants
                  .map(
                    (variant, index) => `
                  <button 
                    class="shade-option ${
                      index === 0 && selectedVariantId ? "active" : ""
                    }" 
                    data-variant-id="${variant.variant_id}"
                    data-shade-name="${variant.shade_name}"
                    data-stock="${variant.stock_quantity}"
                    onclick="selectShade(this)"
                    ${variant.stock_quantity <= 0 ? "disabled" : ""}
                  >
                    <div class="shade-color" style="background-color: ${
                      variant.shade_color
                    };"></div>
                    <span class="shade-name">${variant.shade_name}</span>
                    ${
                      variant.stock_quantity <= 0
                        ? '<span class="out-of-stock-badge">Out of Stock</span>'
                        : ""
                    }
                  </button>
                `
                  )
                  .join("")}
              </div>
              <p id="shade-error" class="shade-error" style="display: none; color: #dc3545; margin-top: 10px; font-size: 0.9em;">
                Please select a shade
              </p>
            </div>
          `;
        }

        // Build the modal content
        detailsContainer.innerHTML = `
          <div class="product-detail-layout">
            <div class="product-detail-image">
              <img src="${data.image_url}" alt="${
          data.name
        }" onerror="this.src='assets/images/placeholder.jpg'">
            </div>
            <div class="product-detail-info">
              <p class="product-category">${data.category}</p>
              <h2 class="product-title">${data.name}</h2>
              <p class="product-price-large">₱${data.price}</p>
              
              <div class="product-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              
              <p class="product-description">${data.description}</p>
              
              ${shadeHTML}
              
              <div class="quantity-selector">
                <label>Quantity</label>
                <div class="quantity-controls">
                  <button class="qty-btn" onclick="decreaseQuantity()" title="Decrease quantity">-</button>
                  <input type="number" id="quantity" value="1" min="1" max="${initialMaxStock}" readonly>
                  <button class="qty-btn" onclick="increaseQuantity()" title="Increase quantity">+</button>
                </div>
              </div>
              
              <button class="btn-add-to-cart" onclick="addToCart(${
                data.product_id
              })" ${initialMaxStock <= 0 ? "disabled" : ""}>
                <i class="fas fa-shopping-cart"></i> ${
                  initialMaxStock > 0 ? "Add to Cart" : "Out of Stock"
                }
              </button>
              
              <p class="stock-info" id="stock-info-text">
                <i class="fas fa-box"></i> 
                ${
                  initialMaxStock > 0
                    ? `${initialMaxStock} items in stock`
                    : "Out of stock"
                }
              </p>
            </div>
          </div>
        `;
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        detailsContainer.innerHTML = `<p style="color: red; text-align: center; padding: 40px;">Network error: ${error.message}</p>`;
      });
  };

  // --- Modal Close Handlers ---
  const modal = document.getElementById("product-detail-modal");
  const closeBtn = modal ? modal.querySelector(".close-btn") : null;

  if (modal && closeBtn) {
    closeBtn.onclick = hideModal;

    window.onclick = (event) => {
      if (event.target == modal) {
        hideModal();
      }
    };
  }

  // --- Other Functions ---
  window.showCheckoutView = () => {
    document.getElementById("shopping-cart-view").style.display = "none";
    document.getElementById("checkout-view").style.display = "block";
  };

  window.showCartView = () => {
    document.getElementById("shopping-cart-view").style.display = "block";
    document.getElementById("checkout-view").style.display = "none";
  };
});
