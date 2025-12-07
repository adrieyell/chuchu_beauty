<?php
session_start();
include('includes/db_config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    $_SESSION['error_message'] = "Please log in to manage your cart";
    header("Location: login.php");
    exit();
}

// Check if cart_id is provided
if (!isset($_GET['cart_id']) || empty($_GET['cart_id'])) {
    $_SESSION['error_message'] = "Invalid cart item";
    header("Location: cart.php");
    exit();
}

$cart_id = intval($_GET['cart_id']);
$user_id = $_SESSION['user_id'];

try {
    // Verify that this cart item belongs to the logged-in user
    $verify_sql = "SELECT cart_id FROM cart WHERE cart_id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $cart_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows == 0) {
        $_SESSION['error_message'] = "Cart item not found or access denied";
        header("Location: cart.php");
        exit();
    }
    
    // Delete the cart item
    $delete_sql = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = "Item removed from cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to remove item from cart";
    }
    
    $delete_stmt->close();
    $verify_stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

$conn->close();
header("Location: cart.php");
exit();
?>