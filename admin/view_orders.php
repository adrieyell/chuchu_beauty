<?php
session_start();
include('../includes/db_config.php');

// Security check: Must be logged in as an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$order_details = [];

// --- A. Fetch All Orders (for the main list) ---
$orders_sql = "SELECT o.order_id, u.fullname, o.total_amount, o.order_date, o.order_status, o.payment_method
               FROM orders o
               JOIN users u ON o.user_id = u.user_id
               ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_sql);

// --- B. Fetch Order Items (when viewing details) ---
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    
    // Fetch individual order details for the summary box
    $detail_sql = "SELECT o.*, u.fullname, u.email, u.phone_number 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.user_id 
                   WHERE o.order_id = ?";
    $stmt = $conn->prepare($detail_sql);
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $detail_result = $stmt->get_result();
    $order_details = $detail_result->fetch_assoc();

    // Fetch the list of items in that order WITH VARIANT INFO
    $items_sql = "SELECT oi.*, p.name, pv.shade_name, pv.shade_color
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                  WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $view_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
}

// --- C. Update Order Status Logic (Processing, Shipped, etc.) ---
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $update_order_id = intval($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $update_order_id);
    
    if ($stmt->execute()) {
        $_SESSION['order_message'] = "<div class='message success-message'>Order #$update_order_id status updated to $new_status.</div>";
    } else {
        $_SESSION['order_message'] = "<div class='message error-message'>Error updating status: " . $stmt->error . "</div>";
    }
    $stmt->close();
    
    // Redirect to clear POST data and show message
    header("Location: view_orders.php");
    exit();
}

// Display session message if exists
if (isset($_SESSION['order_message'])) {
    $message = $_SESSION['order_message'];
    unset($_SESSION['order_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | View Orders</title>
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
        
        .status-pill {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .status-Pending { background-color: #ffc107; color: #333; }
        .status-Processing { background-color: #0dcaf0; }
        .status-Shipped { background-color: #20c997; }
        .status-Delivered { background-color: #198754; }
        .status-Cancelled { background-color: #dc3545; }
        
        .order-detail-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .action-links {
            color: var(--pink-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            background-color: var(--pink-light);
            border-radius: 5px;
            display: inline-block;
            transition: all 0.2s;
        }
        .action-links:hover {
            background-color: var(--pink-dark);
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95em;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .form-control {
            padding: 8px 12px;
            border: 1px solid var(--pink-medium);
            border-radius: 8px;
            font-size: 0.95em;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 15px;
            margin-top: 20px;
        }
        .empty-state i {
            font-size: 4em;
            color: var(--pink-light);
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: var(--pink-dark);
            margin-bottom: 10px;
        }
        .empty-state p {
            color: var(--gray-text);
        }
        
        .shade-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #fff5f8;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-top: 5px;
        }
        .shade-color-mini {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
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
                    <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                    <li><a href="view_orders.php" class="active"><i class="fas fa-shopping-cart"></i> View Orders</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <main class="admin-main-content">
            <h1><i class="fas fa-receipt"></i> Customer Orders</h1>
            <?php echo $message; ?>

            <?php if (!empty($order_details)): ?>
                <!-- ORDER DETAILS VIEW -->
                <a href="view_orders.php" class="btn-secondary" style="margin-bottom: 20px;">
                    <i class="fas fa-arrow-left"></i> Back to All Orders
                </a>
                
                <div class="order-detail-card">
                    <h2>Order #<?php echo $order_details['order_id']; ?> Details</h2>
                    <hr style="margin: 15px 0;">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order_details['fullname']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['phone_number']); ?></p>
                        </div>
                        <div>
                            <p><strong>Order Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($order_details['order_date'])); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment_method']); ?></p>
                            <p><strong>Total Amount:</strong> <span style="color: var(--pink-dark); font-weight: bold; font-size: 1.2em;">₱<?php echo number_format($order_details['total_amount'], 2); ?></span></p>
                        </div>
                    </div>
                    
                    <p><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                    <p style="margin-top: 10px;"><strong>Status:</strong> <span class="status-pill status-<?php echo $order_details['order_status']; ?>"><?php echo $order_details['order_status']; ?></span></p>
                    
                    <h3 style="margin-top: 30px; margin-bottom: 15px;">Update Order Status</h3>
                    <form action="view_orders.php" method="POST" style="display: flex; gap: 10px; align-items: center; max-width: 400px;">
                        <input type="hidden" name="order_id" value="<?php echo $order_details['order_id']; ?>">
                        <select name="status" class="form-control" required style="flex: 1;">
                            <option value="Pending" <?php echo ($order_details['order_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo ($order_details['order_status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo ($order_details['order_status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Delivered" <?php echo ($order_details['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo ($order_details['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-pink" style="width: auto; padding: 10px 20px;">
                            <i class="fas fa-check"></i> Update
                        </button>
                    </form>

                    <h3 style="margin-top: 30px;">Items Ordered</h3>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price at Order</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items_result && $items_result->num_rows > 0): ?>
                                <?php while($item = $items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            <?php if (!empty($item['shade_name'])): ?>
                                                <br>
                                                <span class="shade-badge">
                                                    <div class="shade-color-mini" style="background-color: <?php echo htmlspecialchars($item['shade_color']); ?>;"></div>
                                                    <span>Shade: <?php echo htmlspecialchars($item['shade_name']); ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price_at_order'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--gray-text);">No items found for this order.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: var(--pink-light); font-weight: bold;">
                                <td colspan="3" style="text-align: right;">Total:</td>
                                <td>₱<?php echo number_format($order_details['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php else: ?>
                <!-- ALL ORDERS LIST VIEW -->
                <h2 style="margin-top: 20px;">All Orders</h2>
                <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                                <th>Order Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($order['order_date'])); ?></td>
                                    <td><strong style="color: var(--pink-dark);">₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo $order['order_status']; ?>">
                                            <?php echo $order['order_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_orders.php?view_id=<?php echo $order['order_id']; ?>" class="action-links">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No Orders Yet</h3>
                        <p>Orders from customers will appear here once they start shopping.</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>