<?php
// fetch_product_details.php
// This file returns product details as JSON for the modal popup

header('Content-Type: application/json');

// Include database connection - check both possible file names
if (file_exists('includes/db.php')) {
    require_once('includes/db.php');
} elseif (file_exists('includes/db_config.php')) {
    require_once('includes/db_config.php');
} else {
    echo json_encode(['error' => 'Database configuration file not found']);
    exit;
}

// Get product ID from query parameter
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {   
    
    // Fetch product details from database using OOP style
    $query = "SELECT product_id, name, description, price, image_url, category, stock_quantity 
              FROM products 
              WHERE product_id = ?";

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Ensure all fields are properly formatted
        $product['price'] = number_format($product['price'], 2, '.', '');
        $product['image_url'] = $product['image_url'] ?: 'assets/images/placeholder.jpg';
        $product['category'] = $product['category'] ?: 'Beauty';
        $product['description'] = $product['description'] ?: 'No description available.';
        
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>