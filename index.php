<?php
// Start session at the very top
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChuChu Beauty | Products</title>
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
    
    <!-- Hero Banner Section -->
    <div class="product-hero-banner">
        <div class="hero-content-wrapper">
            <i class="fas fa-heart hero-heart"></i>
            <h1>ChuChu Beauty</h1>
            <p>The best beauty products for you.</p>
        </div>
    </div>

    <!-- Product Grid Section (Overlapping white box) -->
    <div class="product-page-wrapper">
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
                        
                        <div class="product-card" onclick="showProductDetails(<?php echo $productId; ?>)">
                            <div class="product-image-wrapper">
                                <img src="<?php echo $productImage; ?>" 
                                     alt="<?php echo $productName; ?>" 
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </div>
                            <div class="product-info">
                                <p class="product-brand"><?php echo $productCategory; ?></p>
                                <h3 class="product-name"><?php echo $productName; ?></h3>
                                <p class="product-price">₱<?php echo $productPrice; ?></p>
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
</body>
</html>