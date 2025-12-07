<?php
session_start();
include('includes/db_config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$fullname = $conn->real_escape_string($_POST['fullname']);
$phone_number = $conn->real_escape_string($_POST['phone_number']);
$shipping_address = $conn->real_escape_string($_POST['shipping_address']);
$payment_method = $conn->real_escape_string($_POST['payment_method']);

// Fetch cart items with variant information
$cart_query = "SELECT c.product_id, c.quantity, c.variant_id, 
               p.name, p.price, p.stock_quantity,
               pv.stock_quantity as variant_stock
               FROM cart c 
               JOIN products p ON c.product_id = p.product_id 
               LEFT JOIN product_variants pv ON c.variant_id = pv.variant_id
               WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if (!$cart_result || $cart_result->num_rows == 0) {
    $_SESSION['error_message'] = "Your cart is empty!";
    header("Location: cart.php");
    exit();
}

// Calculate total and validate stock
$cart_items = [];
$total_amount = 0;

while ($item = $cart_result->fetch_assoc()) {
    // Check stock availability - use variant stock if variant exists
    $available_stock = !empty($item['variant_id']) ? $item['variant_stock'] : $item['stock_quantity'];
    
    if ($available_stock < $item['quantity']) {
        $_SESSION['error_message'] = "Not enough stock for " . $item['name'];
        header("Location: cart.php");
        exit();
    }
    
    $cart_items[] = $item;
    $total_amount += $item['price'] * $item['quantity'];
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Insert into orders table
    $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, order_status, order_date) 
                  VALUES (?, ?, ?, ?, 'Pending', NOW())";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("idss", $user_id, $total_amount, $shipping_address, $payment_method);
    $order_stmt->execute();
    
    $order_id = $conn->insert_id;
    
    // 2. Insert order items and update stock
    $item_sql = "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_order) VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);
    
    foreach ($cart_items as $item) {
        $variant_id_value = !empty($item['variant_id']) ? $item['variant_id'] : null;
        
        // Insert order item with variant_id
        $item_stmt->bind_param("iiidi", $order_id, $item['product_id'], $variant_id_value, $item['quantity'], $item['price']);
        $item_stmt->execute();
        
        // Update stock - variant stock if variant exists, otherwise product stock
        if (!empty($item['variant_id'])) {
            $stock_sql = "UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE variant_id = ?";
            $stock_stmt = $conn->prepare($stock_sql);
            $stock_stmt->bind_param("ii", $item['quantity'], $item['variant_id']);
        } else {
            $stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
            $stock_stmt = $conn->prepare($stock_sql);
            $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        }
        $stock_stmt->execute();
    }
    
    // 3. Clear user's cart
    $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_cart_sql);
    $clear_stmt->bind_param("i", $user_id);
    $clear_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Set success message
    $_SESSION['order_success'] = true;
    $_SESSION['order_id'] = $order_id;
    $_SESSION['order_total'] = $total_amount;
    
    // Redirect to success page
    header("Location: order_success.php");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $_SESSION['error_message'] = "Failed to process order. Please try again.";
    header("Location: cart.php");
    exit();
}

$conn->close();
?>