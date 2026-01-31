<?php
session_start();
include('../includes/db_config.php');

// Check if the user is logged in and has proper role
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../login.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin';

// Get order ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// Fetch order details
$order_query = "SELECT o.order_id, o.total_amount, o.order_date, o.order_status, 
                o.payment_method, o.shipping_address,
                u.fullname, u.email, u.phone_number
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header("Location: view_orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT order_items.quantity, order_items.price_at_order, 
                products.name as product_name, products.image_url
                FROM order_items
                JOIN products ON order_items.product_id = products.product_id
                WHERE order_items.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin ? 'Admin' : 'Staff'; ?> | Order Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-details-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .detail-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff5f8;
            border-radius: 10px;
        }

        .detail-section h3 {
            color: var(--pink-dark);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--pink-light);
            padding-bottom: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .items-table th {
            background-color: var(--pink-light);
            color: var(--pink-dark);
            font-weight: bold;
        }

        .items-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cfe2ff;
            color: #084298;
        }

        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .back-btn {
            background: var(--pink-dark);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--pink-medium);
            transform: translateY(-2px);
        }

        .total-section {
            text-align: right;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .total-amount {
            font-size: 1.5em;
            color: var(--pink-dark);
            font-weight: bold;
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
            <a href="view_orders.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>

            <h1><i class="fas fa-file-invoice"></i> Order Details - #<?php echo $order['order_id']; ?></h1>

            <div class="order-details-container">
                <!-- Order Information -->
                <div class="detail-section">
                    <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#<?php echo $order['order_id']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date("F j, Y, g:i A", strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="detail-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['fullname']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['phone_number']); ?></span>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="detail-section">
                    <h3><i class="fas fa-truck"></i> Shipping Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars($order['shipping_address']); ?>
                        </span>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="detail-section">
                    <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td>₱<?php echo number_format($item['price_at_order'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><strong>₱<?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="total-section">
                        <h3>Total Amount: <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>