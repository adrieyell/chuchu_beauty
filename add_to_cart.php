<?php
// add_to_cart.php
// Handles adding products to the shopping cart

session_start();
header('Content-Type: application/json');

// Include database connection - check both possible file names
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to cart'
    ]);
    exit;
}

// Get POST data
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$userId = $_SESSION['user_id'];

// Validate inputs
if ($productId <= 0 || $quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product or quantity'
    ]);
    exit;
}

try {
    // Check if product exists and has enough stock
    $checkQuery = "SELECT stock_quantity, name FROM products WHERE product_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if (!$checkResult || $checkResult->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }

    $product = $checkResult->fetch_assoc();

    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock available'
        ]);
        exit;
    }

    // Check if item already exists in cart
    $cartCheckQuery = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $cartCheckStmt = $conn->prepare($cartCheckQuery);
    $cartCheckStmt->bind_param("ii", $userId, $productId);
    $cartCheckStmt->execute();
    $cartCheckResult = $cartCheckStmt->get_result();

    if ($cartCheckResult && $cartCheckResult->num_rows > 0) {
        // Update existing cart item
        $cartItem = $cartCheckResult->fetch_assoc();
        $newQuantity = $cartItem['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock_quantity']) {
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
            // Get total cart count
            $countQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $cartCount = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'message' => $product['name'] . ' quantity updated in cart!',
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
        $insertQuery = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iii", $userId, $productId, $quantity);
        $insertSuccess = $insertStmt->execute();
        
        if ($insertSuccess) {
            // Get total cart count
            $countQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $cartCount = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'message' => $product['name'] . ' added to cart successfully!',
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