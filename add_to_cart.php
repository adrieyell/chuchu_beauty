<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (file_exists('includes/db.php')) {
    require_once('includes/db.php');
} elseif (file_exists('includes/db_config.php')) {
    require_once('includes/db_config.php');
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration error'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to cart'
    ]);
    exit;
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$variantId = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
$userId = $_SESSION['user_id'];

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product or quantity'
    ]);
    exit;
}

try {
    // Check if product has variants
    $variantCheckQuery = "SELECT COUNT(*) as variant_count FROM product_variants WHERE product_id = ?";
    $variantCheckStmt = $conn->prepare($variantCheckQuery);
    $variantCheckStmt->bind_param("i", $productId);
    $variantCheckStmt->execute();
    $variantCheckResult = $variantCheckStmt->get_result();
    $hasVariants = $variantCheckResult->fetch_assoc()['variant_count'] > 0;

    // If product has variants but no variant selected, return error
    if ($hasVariants && !$variantId) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select a shade/variant'
        ]);
        exit;
    }

    // Check stock availability
    if ($variantId) {
        // Check variant stock
        $stockQuery = "SELECT pv.stock_quantity, pv.shade_name, p.name as product_name 
                      FROM product_variants pv 
                      JOIN products p ON pv.product_id = p.product_id 
                      WHERE pv.variant_id = ?";
        $stockStmt = $conn->prepare($stockQuery);
        $stockStmt->bind_param("i", $variantId);
    } else {
        // Check product stock (no variants)
        $stockQuery = "SELECT stock_quantity, name as product_name FROM products WHERE product_id = ?";
        $stockStmt = $conn->prepare($stockQuery);
        $stockStmt->bind_param("i", $productId);
    }
    
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();

    if (!$stockResult || $stockResult->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }

    $stockData = $stockResult->fetch_assoc();
    $productName = $stockData['product_name'];
    if (isset($stockData['shade_name'])) {
        $productName .= ' - ' . $stockData['shade_name'];
    }

    if ($stockData['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock available'
        ]);
        exit;
    }

    // Check if item already exists in cart
    if ($variantId) {
        $cartCheckQuery = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?";
        $cartCheckStmt = $conn->prepare($cartCheckQuery);
        $cartCheckStmt->bind_param("iii", $userId, $productId, $variantId);
    } else {
        $cartCheckQuery = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL";
        $cartCheckStmt = $conn->prepare($cartCheckQuery);
        $cartCheckStmt->bind_param("ii", $userId, $productId);
    }
    
    $cartCheckStmt->execute();
    $cartCheckResult = $cartCheckStmt->get_result();

    if ($cartCheckResult && $cartCheckResult->num_rows > 0) {
        // Update existing cart item
        $cartItem = $cartCheckResult->fetch_assoc();
        $newQuantity = $cartItem['quantity'] + $quantity;
        
        if ($newQuantity > $stockData['stock_quantity']) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot add more items. Stock limit reached.'
            ]);
            exit;
        }
        
        $updateQuery = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $newQuantity, $cartItem['cart_id']);
        $updateSuccess = $updateStmt->execute();
        
        if ($updateSuccess) {
            $countQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $cartCount = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'message' => $productName . ' quantity updated in cart!',
                'cart_count' => $cartCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cart'
            ]);
        }
        
    } else {
        // Insert new cart item
        if ($variantId) {
            $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, variant_id) VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iiii", $userId, $productId, $quantity, $variantId);
        } else {
            $insertQuery = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iii", $userId, $productId, $quantity);
        }
        
        $insertSuccess = $insertStmt->execute();
        
        if ($insertSuccess) {
            $countQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $cartCount = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'message' => $productName . ' added to cart successfully!',
                'cart_count' => $cartCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add item to cart'
            ]);
        }
    }

    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>