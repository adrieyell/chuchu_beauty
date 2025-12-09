<?php

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChuChu Beauty | Products</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php 
    // Check if header file exists before including
    if (file_exists('includes/header.php')) {
        include('includes/header.php'); 
    }
    ?>

    <div class="product-hero-banner">
        <div class="hero-sparkle"></div>
        <div class="hero-sparkle"></div>
        <div class="hero-sparkle"></div>
        
        <div class="hero-content-wrapper">
            <i class="fas fa-heart hero-heart"></i>
            <h1>ChuChu Beauty</h1>
            <p>The best beauty products for you.</p>
            <p class="hero-subtext">✨ Premium Quality • Affordable Prices • Fast Delivery ✨</p>
        </div>
    </div>

    <div class="product-page-wrapper">
        <section class="filter-section">
            <div class="search-filter-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." onkeyup="filterProducts()">
                </div>
<div class="filter-buttons">
    <button class="filter-btn active" onclick="filterByCategory('all')">All Products</button>
    <button class="filter-btn" onclick="filterByCategory('Lip Product')">Lip Product</button>
    <button class="filter-btn" onclick="filterByCategory('Cheek Product')">Cheek Product</button>
    <button class="filter-btn" onclick="filterByCategory('Face Product')">Face Product</button>
</div>
                <div class="sort-dropdown">
                    <label for="sortSelect"><i class="fas fa-sort-amount-down"></i> Sort by:</label>
                    <select id="sortSelect" onchange="sortProducts()">
                        <option value="newest">Newest First</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="name">Name: A-Z</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="product-grid-section">
            <h2>Product Catalog</h2>
            <div class="product-grid" id="productGrid">
                <?php
                // Check if database connection file exists
                if (file_exists('includes/db.php')) {
                    require_once('includes/db.php');
                } elseif (file_exists('includes/db_config.php')) {
                    require_once('includes/db_config.php');
                } else {
                    echo '<p style="text-align: center; padding: 40px; color: red;">Database configuration file not found!</p>';
                    exit;
                }

                // Fetch all products from database using OOP style
                $query = "SELECT product_id, name, price, image_url, category, stock_quantity 
                          FROM products 
                          WHERE stock_quantity > 0 
                          ORDER BY product_id DESC";
                          
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    while ($product = $result->fetch_assoc()) {
                        $productId = htmlspecialchars($product['product_id']);
                        $productName = htmlspecialchars($product['name']);
                        $productPrice = number_format($product['price'], 2);
                        $productImage = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/placeholder.jpg';
                        $productCategory = !empty($product['category']) ? htmlspecialchars($product['category']) : 'Beauty';
                        ?>
                        
                        <div class="product-card" data-category="<?php echo $productCategory; ?>" data-name="<?php echo strtolower($productName); ?>" data-price="<?php echo $product['price']; ?>" onclick="showProductDetails(<?php echo $productId; ?>)">
                            <div class="product-badge">New</div>
                            <div class="product-image-wrapper">
                                <img src="<?php echo $productImage; ?>" 
                                     alt="<?php echo $productName; ?>" 
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </div>
                            <div class="product-info">
                                <p class="product-brand"><?php echo $productCategory; ?></p>
                                <h3 class="product-name"><?php echo $productName; ?></h3>
                                <p class="product-price">₱<?php echo $productPrice; ?></p>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <span>(4.5)</span>
                                </div>
                            </div>
                            <button class="quick-add-btn" onclick="event.stopPropagation(); showProductDetails(<?php echo $productId; ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <?php
                    }
                } else {
                    echo '<p style="text-align: center; width: 100%; color: var(--gray-text); padding: 40px;">No products available at the moment.</p>';
                }
                
                // Close connection
                if (isset($conn)) {
                    $conn->close();
                }
                ?>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="feature-card">
                <i class="fas fa-shipping-fast"></i>
                <h3>Fast Delivery</h3>
                <p>Get your orders within 3-5 business days</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-undo-alt"></i>
                <h3>Easy Returns</h3>
                <p>30-day hassle-free return policy</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Payment</h3>
                <p>100% secure payment processing</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>We're here to help you anytime</p>
            </div>
        </section>
    </div>

    <!-- Product Detail Modal (ID matches your script.js) -->
    <div id="product-detail-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="product-details-view">
                <!-- Product details will be injected here by JavaScript -->
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Product filtering and search functions
        function filterProducts() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const productName = product.getAttribute('data-name');
                if (productName.includes(searchValue)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        function filterByCategory(category) {
            const products = document.querySelectorAll('.product-card');
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            products.forEach(product => {
                const productCategory = product.getAttribute('data-category');
                if (category === 'all' || productCategory === category) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        function sortProducts() {
            const sortValue = document.getElementById('sortSelect').value;
            const grid = document.getElementById('productGrid');
            const products = Array.from(document.querySelectorAll('.product-card'));
            
            products.sort((a, b) => {
                switch(sortValue) {
                    case 'price-low':
                        return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                    case 'price-high':
                        return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                    case 'name':
                        return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                    default:
                        return 0;
                }
            });
            
            products.forEach(product => grid.appendChild(product));
        }
    </script>
</body>
</html>