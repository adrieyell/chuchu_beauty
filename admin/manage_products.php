<?php
session_start();
include('../includes/db_config.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$edit_product = null;

// DELETE VARIANT LOGIC
if (isset($_GET['action']) && $_GET['action'] == 'delete_variant' && isset($_GET['variant_id'])) {
    $variant_id = intval($_GET['variant_id']);
    $product_id = intval($_GET['product_id']);
    
    $delete_sql = "DELETE FROM product_variants WHERE variant_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $variant_id);
    
    if ($stmt->execute()) {
        header("Location: manage_products.php?action=edit&id=" . $product_id . "&msg=variant_deleted");
    }
    exit();
}

// DELETE PRODUCT LOGIC
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    $img_query = "SELECT image_url FROM products WHERE product_id = ?";
    $img_stmt = $conn->prepare($img_query);
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    
    if ($img_result->num_rows > 0) {
        $img_row = $img_result->fetch_assoc();
        if (!empty($img_row['image_url']) && file_exists('../' . $img_row['image_url'])) {
            unlink('../' . $img_row['image_url']);
        }
    }
    
    // Delete variants first
    $delete_variants = "DELETE FROM product_variants WHERE product_id = ?";
    $stmt = $conn->prepare($delete_variants);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    // Delete product
    $delete_sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $message = "<div class='message success-message'>Product deleted successfully!</div>";
    }
    $stmt->close();
    
    header("Location: manage_products.php");
    exit();
}

// ADD/UPDATE PRODUCT LOGIC 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category'] ?? 'Beauty');
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    
    $image_url = '';
    $upload_success = true;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        
        $file_type = $_FILES['product_image']['type'];
        $file_size = $_FILES['product_image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $message = "<div class='message error-message'>Invalid file type. Only JPG, PNG, GIF, and WEBP allowed.</div>";
            $upload_success = false;
        } elseif ($file_size > $max_size) {
            $message = "<div class='message error-message'>File too large. Maximum size is 5MB.</div>";
            $upload_success = false;
        } else {
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = '../assets/images/products/' . $new_filename;
            
            if (!is_dir('../assets/images/products/')) {
                mkdir('../assets/images/products/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = 'assets/images/products/' . $new_filename;
                
                if ($product_id) {
                    $old_img_query = "SELECT image_url FROM products WHERE product_id = ?";
                    $old_img_stmt = $conn->prepare($old_img_query);
                    $old_img_stmt->bind_param("i", $product_id);
                    $old_img_stmt->execute();
                    $old_img_result = $old_img_stmt->get_result();
                    
                    if ($old_img_result->num_rows > 0) {
                        $old_img = $old_img_result->fetch_assoc();
                        if (!empty($old_img['image_url']) && file_exists('../' . $old_img['image_url'])) {
                            unlink('../' . $old_img['image_url']);
                        }
                    }
                }
            } else {
                $message = "<div class='message error-message'>Failed to upload image.</div>";
                $upload_success = false;
            }
        }
    }
    
    if ($upload_success) {
        if ($product_id) {
            // UPDATE PRODUCT
            if (!empty($image_url)) {
                $sql = "UPDATE products SET name = ?, price = ?, stock_quantity = ?, description = ?, category = ?, image_url = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdisssi", $name, $price, $stock_quantity, $description, $category, $image_url, $product_id);
            } else {
                $sql = "UPDATE products SET name = ?, price = ?, stock_quantity = ?, description = ?, category = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdissi", $name, $price, $stock_quantity, $description, $category, $product_id);
            }
            $success_msg = "Product updated successfully!";
        } else {
            // INSERT NEW PRODUCT
            $sql = "INSERT INTO products (name, price, stock_quantity, description, category, image_url) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdisss", $name, $price, $stock_quantity, $description, $category, $image_url);
            $success_msg = "Product added successfully!";
        }

        if ($stmt->execute()) {
            $saved_product_id = $product_id ? $product_id : $conn->insert_id;
            
            // HANDLE SHADES/VARIANTS
            if (isset($_POST['shade_names']) && is_array($_POST['shade_names'])) {
                if ($product_id) {
                    $delete_variants = "DELETE FROM product_variants WHERE product_id = ?";
                    $del_stmt = $conn->prepare($delete_variants);
                    $del_stmt->bind_param("i", $saved_product_id);
                    $del_stmt->execute();
                }
                
                // INSERT NEW VARIANTS
                $variant_sql = "INSERT INTO product_variants (product_id, shade_name, shade_color, stock_quantity) VALUES (?, ?, ?, ?)";
                $variant_stmt = $conn->prepare($variant_sql);
                
                foreach ($_POST['shade_names'] as $index => $shade_name) {
                    if (!empty($shade_name)) {
                        $shade_color = $_POST['shade_colors'][$index];
                        $shade_stock = intval($_POST['shade_stocks'][$index]);
                        
                        $variant_stmt->bind_param("issi", $saved_product_id, $shade_name, $shade_color, $shade_stock);
                        $variant_stmt->execute();
                    }
                }
                $variant_stmt->close();
            }
            
            $message = "<div class='message success-message'>$success_msg</div>";
        } else {
            $message = "<div class='message error-message'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// EDIT LOGIC
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $edit_sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($edit_sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    
    if ($edit_result->num_rows == 1) {
        $edit_product = $edit_result->fetch_assoc();
    }
    $stmt->close();
}

// DISPLAY RECORDS
$products_sql = "SELECT product_id, name, price, stock_quantity, image_url, category FROM products ORDER BY product_id DESC";
$products_result = $conn->query($products_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Manage Products</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background-color: white;
        }
        .product-table th, .product-table td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        .product-table th { 
            background-color: #ffafc9; 
            color: white; 
            font-weight: bold;
        }
        .product-table tr:nth-child(even) { 
            background-color: #fce4e8; 
        }
        .product-table tr:hover {
            background-color: #ffe6f0;
        }
        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background-color: var(--pink-light);
            padding: 3px;
        }
        .action-links a { 
            margin-right: 10px; 
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
        }
        .action-links .edit-btn {
            background-color: var(--pink-dark);
            color: white;
        }
        .action-links .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        /* Variant Management Styles */
        .variant-section {
            background-color: #fff5f8;
            padding: 25px;
            margin: 30px 0;
            border-radius: 15px;
            border: 2px solid #ffafc9;
        }
        .variant-list {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }
        .variant-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            border: 1px solid #ffd4e5;
        }
        .variant-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .color-swatch {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .shade-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 15px;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            border: 1px solid #ffd4e5;
        }
        .form-group-inline {
            display: flex;
            flex-direction: column;
        }
        .form-group-inline label {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--pink-dark);
            font-size: 0.9em;
        }
        .color-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .color-input-wrapper input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-small {
            padding: 8px 15px;
            font-size: 0.85em;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        .btn-add-shade {
            background-color: #28a745;
            color: white;
            margin-top: 15px;
            padding: 10px 20px;
        }
        .btn-remove-shade {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2><i class="fas fa-crown"></i> Admin Panel</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage_products.php" class="active"><i class="fas fa-box"></i> Manage Products</a></li>
                    <li><a href="view_orders.php"><i class="fas fa-shopping-cart"></i> View Orders</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <main class="admin-main-content">
            <h1><i class="fas fa-box-open"></i> Manage Products</h1>
            
            <?php 
            echo $message; 
            if (isset($_GET['msg'])) {
                if ($_GET['msg'] == 'variant_added') {
                    echo "<div class='message success-message'>Shade/variant added successfully!</div>";
                } elseif ($_GET['msg'] == 'variant_deleted') {
                    echo "<div class='message success-message'>Shade/variant deleted successfully!</div>";
                }
            }
            ?>

            <h2><?php echo $edit_product ? 'Update Product' : 'Add New Product'; ?></h2>
            <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                <?php endif; ?>
                
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>

                <label>Category:</label>
                <input type="text" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : 'Beauty'; ?>" placeholder="e.g., Lipstick, Blush, Skincare">

                <label>Price (₱):</label>
                <input type="number" name="price" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>

                <label>Stock Quantity:</label>
                <input type="number" name="stock_quantity" value="<?php echo $edit_product ? $edit_product['stock_quantity'] : ''; ?>" required>
                
                <label>Description:</label>
                <textarea name="description" rows="4" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                
                <div class="file-input-wrapper">
                    <label>Product Image:</label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" onchange="previewImage(event)">
                    <small style="color: var(--gray-text); display: block; margin-top: 5px;">
                        Accepted: JPG, PNG, GIF, WEBP (Max 5MB)
                    </small>
                    
                    <?php if ($edit_product && !empty($edit_product['image_url'])): ?>
                        <div style="margin-top: 10px;">
                            <p style="font-weight: bold; color: var(--pink-dark);">Current Image:</p>
                            <img src="../<?php echo htmlspecialchars($edit_product['image_url']); ?>" class="current-image" style="max-width: 200px; border-radius: 10px; border: 2px solid var(--pink-medium);" alt="Current product image">
                        </div>
                    <?php endif; ?>
                    
                    <img id="image_preview" class="image-preview" style="max-width: 200px; border-radius: 10px; display: none;" alt="Image preview">
                </div>

                <div class="variant-section">
                    <h3><i class="fas fa-palette"></i> Product Shades/Variants (Optional)</h3>
                    <p style="color: var(--gray-text); margin-bottom: 15px; font-size: 0.9em;">
                        Add color shades or variants for this product (e.g., different lipstick colors)
                    </p>
                    
                    <div id="shades-container">
                        <?php 
                        // If editing, load existing variants
                        if ($edit_product) {
                            $variants_sql = "SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id";
                            $variants_stmt = $conn->prepare($variants_sql);
                            $variants_stmt->bind_param("i", $edit_product['product_id']);
                            $variants_stmt->execute();
                            $variants_result = $variants_stmt->get_result();
                            
                            if ($variants_result->num_rows > 0) {
                                while($variant = $variants_result->fetch_assoc()):
                        ?>
                            <div class="shade-row">
                                <div class="form-group-inline">
                                    <label>Shade Name:</label>
                                    <input type="text" name="shade_names[]" value="<?php echo htmlspecialchars($variant['shade_name']); ?>" placeholder="e.g., Ruby Red">
                                </div>
                                <div class="form-group-inline">
                                    <label>Color:</label>
                                    <input type="color" name="shade_colors[]" value="<?php echo htmlspecialchars($variant['shade_color']); ?>">
                                </div>
                                <div class="form-group-inline">
                                    <label>Stock:</label>
                                    <input type="number" name="shade_stocks[]" value="<?php echo $variant['stock_quantity']; ?>" min="0">
                                </div>
                                <button type="button" class="btn-small btn-remove-shade" onclick="removeShadeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php 
                                endwhile;
                            }
                        }
                        ?>
                        <!-- Initial empty shade row -->
                        <div class="shade-row">
                            <div class="form-group-inline">
                                <label>Shade Name:</label>
                                <input type="text" name="shade_names[]" placeholder="e.g., Ruby Red">
                            </div>
                            <div class="form-group-inline">
                                <label>Color:</label>
                                <input type="color" name="shade_colors[]" value="#ffc0cb">
                            </div>
                            <div class="form-group-inline">
                                <label>Stock:</label>
                                <input type="number" name="shade_stocks[]" value="0" min="0">
                            </div>
                            <button type="button" class="btn-small btn-remove-shade" onclick="removeShadeRow(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-small btn-add-shade" onclick="addShadeRow()">
                        <i class="fas fa-plus"></i> Add Another Shade
                    </button>
                </div>
                
                <button type="submit" name="submit_product" class="btn-pink">
                    <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                </button>
                
                <?php if ($edit_product): ?>
                    <a href="manage_products.php" class="btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>

            <hr style="margin: 40px 0;">

            <h2>Product Records</h2>
            <?php if ($products_result->num_rows > 0): ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $products_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['product_id']; ?></td>
                                <td>
                                    <?php if (!empty($row['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" class="product-thumbnail" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/placeholder.jpg" class="product-thumbnail" alt="No image">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category'] ?? 'Beauty'); ?></td>
                                <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo $row['stock_quantity']; ?></td>
                                <td class="action-links">
                                    <a href="manage_products.php?action=edit&id=<?php echo $row['product_id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                    <a href="manage_products.php?action=delete&id=<?php echo $row['product_id']; ?>" 
                                       class="delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; background-color: white; border-radius: 10px;">
                    No products found in the database.
                </p>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function previewImage(event) {
            const preview = document.getElementById('image_preview');
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
        
        function addShadeRow() {
            const container = document.getElementById('shades-container');
            const newRow = document.createElement('div');
            newRow.className = 'shade-row';
            newRow.innerHTML = `
                <div class="form-group-inline">
                    <label>Shade Name:</label>
                    <input type="text" name="shade_names[]" placeholder="e.g., Ruby Red">
                </div>
                <div class="form-group-inline">
                    <label>Color:</label>
                    <input type="color" name="shade_colors[]" value="#ffc0cb">
                </div>
                <div class="form-group-inline">
                    <label>Stock:</label>
                    <input type="number" name="shade_stocks[]" value="0" min="0">
                </div>
                <button type="button" class="btn-small btn-remove-shade" onclick="removeShadeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }
        
        function removeShadeRow(button) {
            const row = button.closest('.shade-row');
            const container = document.getElementById('shades-container');
            
            // Keep at least one row
            if (container.children.length > 1) {
                row.remove();
            } else {
                // Clear the inputs instead of removing
                row.querySelectorAll('input').forEach(input => {
                    if (input.type === 'color') {
                        input.value = '#ffc0cb';
                    } else if (input.type === 'number') {
                        input.value = '0';
                    } else {
                        input.value = '';
                    }
                });
            }
        }
    </script>
</body>
</html>