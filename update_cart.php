<?php
// update_cart.php
session_start();
include('includes/db_config.php'); // Need database connection

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['change'])) {
    $product_id = intval($_GET['id']);
    $change = intval($_GET['change']); // Should be +1 or -1
    $user_id = $_SESSION['user_id'];

    // 1. Fetch current quantity and stock from the database
    $fetch_query = "SELECT c.quantity, p.stock_quantity FROM cart c 
                    JOIN products p ON c.product_id = p.product_id 
                    WHERE c.user_id = ? AND c.product_id = ?";
    
    $fetch_stmt = $conn->prepare($fetch_query);
    $fetch_stmt->bind_param("ii", $user_id, $product_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $current_quantity = $row['quantity'];
        $stock_quantity = $row['stock_quantity'];
        $new_quantity = $current_quantity + $change;

        if ($new_quantity <= 0) {
            // Case 1: New quantity is 0 or less -> Remove item
            $delete_query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("ii", $user_id, $product_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "Item removed from cart.";
            } else {
                $_SESSION['error_message'] = "Failed to remove item: " . $conn->error;
            }
            $delete_stmt->close();

        } elseif ($new_quantity > $stock_quantity) {
            // Case 2: New quantity exceeds stock -> Display error (preventing the update)
            $_SESSION['error_message'] = "Cannot update quantity. Only " . $stock_quantity . " items are available in stock.";

        } else {
            // Case 3: Update quantity (new quantity is valid)
            $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Cart quantity updated to " . $new_quantity . ".";
            } else {
                $_SESSION['error_message'] = "Failed to update quantity: " . $conn->error;
            }
            $update_stmt->close();
        }
    } else {
        $_SESSION['error_message'] = "Product not found in your cart.";
    }

    $fetch_stmt->close();
}

$conn->close();

// Redirect back to the cart page to refresh the display
header("Location: cart.php");
exit();
?>