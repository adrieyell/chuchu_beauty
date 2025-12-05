<?php
session_start();
include('includes/db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Use prepared statement for security
    $delete_query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($delete_query);
    
    // Bind parameters (ii = two integers)
    $stmt->bind_param("ii", $user_id, $product_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item removed from cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to remove item from cart: " . $conn->error;
    }
    $stmt->close(); // Close the statement
}

$conn->close();

// Redirect back to the cart page to refresh the display
header("Location: cart.php");
exit();