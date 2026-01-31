<?php
session_start();
include('../includes/db_config.php');
include('../includes/email_config.php');

// Check if the user is logged in and has proper role
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../login.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin';

// Get filter type (active or finished)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['new_status']);
    
    // Update the order status
    $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if ($update_stmt->execute()) {
        // Fetch order details and customer email for notification
        $order_info_query = "SELECT o.order_id, o.total_amount, o.order_date, 
                             u.fullname, u.email
                             FROM orders o
                             JOIN users u ON o.user_id = u.user_id
                             WHERE o.order_id = ?";
        $order_info_stmt = $conn->prepare($order_info_query);
        $order_info_stmt->bind_param("i", $order_id);
        $order_info_stmt->execute();
        $order_info = $order_info_stmt->get_result()->fetch_assoc();
        
        if ($order_info) {
            // Prepare order data for email
            $order_data = [
                'order_id' => $order_info['order_id'],
                'customer_name' => $order_info['fullname'],
                'order_date' => $order_info['order_date'],
                'total' => $order_info['total_amount']
            ];
            
            // Send status update email
            $email_subject = "Order Status Update - Sol Beauty Order #" . $order_id;
            $email_body = getOrderStatusUpdateEmailTemplate($order_data, $new_status);
            
            sendOrderEmail($order_info['email'], $email_subject, $email_body, $order_info['fullname']);
        }
        
        $_SESSION['success_message'] = "Order status updated successfully and customer notified!";
    } else {
        $_SESSION['error_message'] = "Failed to update order status.";
    }
    
    header("Location: view_orders.php?filter=" . $filter);
    exit();
}

// Fetch orders based on filter
if ($filter === 'finished') {
    // Finished orders: Delivered or Cancelled
    $orders_query = "SELECT o.order_id, o.total_amount, o.order_date, o.order_status, o.payment_method,
                     u.fullname, u.email, u.phone_number
                     FROM orders o
                     JOIN users u ON o.user_id = u.user_id
                     WHERE o.order_status IN ('Delivered', 'Cancelled')
                     ORDER BY o.order_date DESC";
} else {
    // Active orders: Pending, Processing, Shipped
    $orders_query = "SELECT o.order_id, o.total_amount, o.order_date, o.order_status, o.payment_method,
                     u.fullname, u.email, u.phone_number
                     FROM orders o
                     JOIN users u ON o.user_id = u.user_id
                     WHERE o.order_status IN ('Pending', 'Processing', 'Shipped')
                     ORDER BY o.order_date DESC";
}
$orders_result = $conn->query($orders_query);

// Get counts for tabs
$active_count_query = "SELECT COUNT(*) as count FROM orders WHERE order_status IN ('Pending', 'Processing', 'Shipped')";
$active_count = $conn->query($active_count_query)->fetch_assoc()['count'];

$finished_count_query = "SELECT COUNT(*) as count FROM orders WHERE order_status IN ('Delivered', 'Cancelled')";
$finished_count = $conn->query($finished_count_query)->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin ? 'Admin' : 'Staff'; ?> | View Orders</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .orders-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        /* Tab Navigation Styles */
        .order-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 3px solid var(--pink-light);
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 25px;
            background: transparent;
            border: none;
            color: var(--gray-text);
            font-weight: 600;
            font-size: 1.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -3px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: var(--pink-dark);
            background: rgba(255, 105, 180, 0.05);
        }

        .tab-btn.active {
            color: var(--pink-dark);
            border-bottom-color: var(--pink-dark);
            background: rgba(255, 105, 180, 0.05);
        }

        .tab-badge {
            background: var(--pink-dark);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .tab-btn.active .tab-badge {
            background: white;
            color: var(--pink-dark);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .orders-table th {
            background-color: var(--pink-light);
            color: var(--pink-dark);
            font-weight: bold;
        }

        .orders-table tr:hover {
            background-color: #fff5f8;
        }

        .status-select {
            padding: 8px 12px;
            border: 2px solid var(--pink-light);
            border-radius: 8px;
            color: #333;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--pink-dark);
        }

        .status-select:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .update-btn {
            background: var(--pink-dark);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            background: var(--pink-medium);
            transform: translateY(-2px);
        }

        .update-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .view-details-btn {
            background: #0dcaf0;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: #0aa2c0;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-left: 4px solid #dc3545;
        }

        .status-badge-table {
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-text);
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: var(--gray-text);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2><i class="<?php echo $is_admin ? 'fas fa-crown' : 'fas fa-user-tie'; ?>"></i> <?php echo $is_admin ? 'Admin' : 'Staff'; ?> Panel</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <?php if ($is_admin): ?>
                    <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                    <?php endif; ?>
                    <li><a href="view_orders.php" class="active"><i class="fas fa-shopping-cart"></i> View Orders</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="admin-main-content">
            <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="orders-container">
                <!-- Tab Navigation -->
                <div class="order-tabs">
                    <a href="view_orders.php?filter=active" class="tab-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> 
                        Active Orders 
                        <span class="tab-badge"><?php echo $active_count; ?></span>
                    </a>
                    <a href="view_orders.php?filter=finished" class="tab-btn <?php echo $filter === 'finished' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> 
                        Finished Orders 
                        <span class="tab-badge"><?php echo $finished_count; ?></span>
                    </a>
                </div>

                <h3>
                    <i class="fas fa-<?php echo $filter === 'finished' ? 'check-circle' : 'clock'; ?>"></i> 
                    <?php echo $filter === 'finished' ? 'Finished Orders' : 'Active Orders'; ?>
                </h3>
                
                <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <?php 
                                    $is_finished = in_array($order['order_status'], ['Delivered', 'Cancelled']);
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small><br>
                                        <small><?php echo htmlspecialchars($order['phone_number']); ?></small>
                                    </td>
                                    <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php if ($filter === 'finished'): ?>
                                            <!-- Just show the status badge for finished orders -->
                                            <span class="status-badge-table status-<?php echo $order['order_status']; ?>">
                                                <?php echo htmlspecialchars($order['order_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <!-- Show editable dropdown for active orders -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <select name="new_status" class="status-select" <?php echo $is_finished ? 'disabled' : ''; ?>>
                                                    <option value="Pending" <?php echo $order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Processing" <?php echo $order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="Shipped" <?php echo $order['order_status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="Delivered" <?php echo $order['order_status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="Cancelled" <?php echo $order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" name="update_status" class="update-btn" <?php echo $is_finished ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-sync-alt"></i> Update
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="view-details-btn">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-<?php echo $filter === 'finished' ? 'check-circle' : 'inbox'; ?>"></i>
                        <h3>No <?php echo $filter === 'finished' ? 'finished' : 'active'; ?> orders found</h3>
                        <p>
                            <?php 
                                if ($filter === 'finished') {
                                    echo "Completed and cancelled orders will appear here.";
                                } else {
                                    echo "Pending, processing, and shipped orders will appear here.";
                                }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>