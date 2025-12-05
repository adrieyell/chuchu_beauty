<?php
// Include database connection and session check (if needed)
include('includes/db_connect.php'); 
// include('includes/admin_check.php'); 

$product_data = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    // Fetch existing product data
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $product_data = $result->fetch_assoc();
    } else {
        // Redirect if product not found
        header("Location: manage-products.php?error=Product not found");
        exit();
    }
    $stmt->close();
} else {
    // Redirect if no ID is provided
    header("Location: manage-products.php?error=No product ID provided");
    exit();
}
?>

<div class="admin-layout">
    <?php include('includes/sidebar.php'); ?>
    <div class="admin-main-content">
        <h1>Admin Panel</h1>
        <h2>Edit Product: <?php echo htmlspecialchars($product_data['name']); ?></h2>
        <div class="admin-content">
            
            <form action="update_product.php" method="POST">
                
                <input type="hidden" name="product_id" value="<?php echo $product_data['product_id']; ?>">

                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" required>

                <label for="price">Price (₱):</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product_data['price']); ?>" required>

                <label for="stock">Stock Quantity:</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($product_data['description']); ?></textarea>

                <button type="submit" name="update_product" class="btn-pink" style="margin-top: 20px;">Update Product</button>
            </form>
        </div>
    </div>
</div>