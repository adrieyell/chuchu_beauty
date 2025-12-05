<?php
// Include database connection
include('includes/db_connect.php'); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {

    // 1. Get and sanitize input data
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_SANITIZE_NUMBER_INT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    // 2. Basic validation
    if (!$product_id || !$name || !$price || !$stock || !$description) {
        header("Location: edit-product.php?id=$product_id&error=All fields are required.");
        exit();
    }

    // 3. Prepare SQL UPDATE statement
    $sql = "UPDATE products SET name = ?, price = ?, stock_quantity = ?, description = ? WHERE product_id = ?";

    $stmt = $conn->prepare($sql);

    // Bind parameters: (s=string, d=double/float, i=integer)
    $stmt->bind_param("sdisi", $name, $price, $stock, $description, $product_id);

    // 4. Execute and check result
    if ($stmt->execute()) {
        // Success
        header("Location: manage-products.php?success=Product updated successfully!");
        exit();
    } else {
        // Error
        header("Location: edit-product.php?id=$product_id&error=Database error: " . $stmt->error);
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    // If accessed directly without POST data
    header("Location: manage-products.php");
    exit();
}
?>