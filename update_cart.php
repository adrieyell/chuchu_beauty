<?php
session_start();
include('includes/db_config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    $_SESSION['error_message'] = "Please log in to manage your cart";
    header("Location: login.php");
    exit();
}

// Check if required parameters are provided
if (!isset($_GET['cart_id']) || !isset($_GET['change'])) {
    $_SESSION['error_message'] = "Invalid request";
    header("Location: cart.php");
    exit();
}

$cart_id = intval($_GET['cart_id']);
$change = intval($_GET['change']); // +1 or -1
$user_id = $_SESSION['user_id'];

try {
    // Fetch current cart item with stock info
    $fetch_sql = "SELECT c.quantity, c.variant_id, c.product_id,
                  COALESCE(pv.stock_quantity, p.stock_quantity) as available_stock
                  FROM cart c
                  JOIN products p ON c.product_id = p.product_id
                  LEFT JOIN product_variants pv ON c.variant_id = pv.variant_id
                  WHERE c.cart_id = ? AND c.user_id = ?";
    
    $fetch_stmt = $conn->prepare($fetch_sql);
    $fetch_stmt->bind_param("ii", $cart_id, $user_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = "Cart item not found";
        header("Location: cart.php");
        exit();
    }
    
    $cart_item = $result->fetch_assoc();
    $current_quantity = $cart_item['quantity'];
    $available_stock = $cart_item['available_stock'];
    $new_quantity = $current_quantity + $change;
    
    // Validate new quantity
    if ($new_quantity < 1) {
        $_SESSION['error_message'] = "Quantity cannot be less than 1. Use delete button to remove item.";
        header("Location: cart.php");
        exit();
    }
    
    if ($new_quantity > $available_stock) {
        $_SESSION['error_message'] = "Cannot add more items. Maximum stock is " . $available_stock;
        header("Location: cart.php");
        exit();
    }
    
    // Update the cart quantity
    $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Cart updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update cart";
    }
    
    $update_stmt->close();
    $fetch_stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

$conn->close();
header("Location: cart.php");
exit();
?>