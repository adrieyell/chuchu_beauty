<?php
header('Content-Type: application/json; charset=utf-8');

// Include database configuration
if (file_exists('includes/db.php')) {
    require_once('includes/db.php');
} elseif (file_exists('includes/db_config.php')) {
    require_once('includes/db_config.php');
} else {
    echo json_encode(['error' => 'Database configuration file not found']);
    exit;
}

// Get and validate product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {   
    // Fetch product details
    $query = "SELECT product_id, name, brand, description, price, image_url, category, stock_quantity 
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
        
        // Format product data with proper defaults
        $product['price'] = number_format($product['price'], 2, '.', '');
        $product['image_url'] = !empty($product['image_url']) ? $product['image_url'] : 'assets/images/placeholder.jpg';
        $product['category'] = !empty($product['category']) ? $product['category'] : 'Beauty';
        $product['brand'] = !empty($product['brand']) ? $product['brand'] : '';
        $product['description'] = !empty($product['description']) ? $product['description'] : 'No description available.';
        $product['stock_quantity'] = intval($product['stock_quantity']);
        
        // Fetch variants/shades for this product
        $variants_query = "SELECT variant_id, shade_name, shade_color, stock_quantity 
                          FROM product_variants 
                          WHERE product_id = ? 
                          ORDER BY variant_id";
        
        $variants_stmt = $conn->prepare($variants_query);
        
        if ($variants_stmt) {
            $variants_stmt->bind_param("i", $productId);
            $variants_stmt->execute();
            $variants_result = $variants_stmt->get_result();
            
            $variants = [];
            while ($variant = $variants_result->fetch_assoc()) {
                $variants[] = [
                    'variant_id' => intval($variant['variant_id']),
                    'shade_name' => $variant['shade_name'],
                    'shade_color' => $variant['shade_color'],
                    'stock_quantity' => intval($variant['stock_quantity'])
                ];
            }
            
            $product['variants'] = $variants;
            $product['has_variants'] = count($variants) > 0;
            
            $variants_stmt->close();
        } else {
            // If variants query fails, just return empty array
            $product['variants'] = [];
            $product['has_variants'] = false;
        }
        
        // Return the product data as JSON
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Product not found with ID: ' . $productId]);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>