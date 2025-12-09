<?php
session_start();
include('../includes/db_config.php');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch dashboard statistics
$stats = [];

// Total Products
$products_query = "SELECT COUNT(*) as total FROM products";
$products_result = $conn->query($products_query);
$stats['total_products'] = $products_result->fetch_assoc()['total'];

// Low Stock Products (less than 10)
$low_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity < 10 AND stock_quantity > 0";
$low_stock_result = $conn->query($low_stock_query);
$stats['low_stock'] = $low_stock_result->fetch_assoc()['total'];

// Out of Stock Products
$out_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0";
$out_stock_result = $conn->query($out_stock_query);
$stats['out_of_stock'] = $out_stock_result->fetch_assoc()['total'];

// Total Orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = $conn->query($orders_query);
$stats['total_orders'] = $orders_result->fetch_assoc()['total'];

// Pending Orders
$pending_query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'";
$pending_result = $conn->query($pending_query);
$stats['pending_orders'] = $pending_result->fetch_assoc()['total'];

// Total Revenue
$revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'Cancelled'";
$revenue_result = $conn->query($revenue_query);
$stats['total_revenue'] = $revenue_result->fetch_assoc()['total'] ?? 0;

// Recent Orders
$recent_orders_query = "SELECT o.order_id, u.fullname, o.total_amount, o.order_date, o.order_status 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC LIMIT 5";
$recent_orders = $conn->query($recent_orders_query);

// Top Selling Products
$top_products_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id 
                       GROUP BY oi.product_id 
                       ORDER BY total_sold DESC LIMIT 5";
$top_products = $conn->query($top_products_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: var(--pink-light);
            border-radius: 50%;
            transform: translate(30%, -30%);
            opacity: 0.3;
        }

        .stat-icon {
            font-size: 3em;
            color: var(--pink-dark);
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--pink-dark);
            margin: 10px 0;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: var(--gray-text);
            font-size: 1em;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .stat-card.warning .stat-icon,
        .stat-card.warning .stat-value {
            color: #ff9800;
        }

        .stat-card.danger .stat-icon,
        .stat-card.danger .stat-value {
            color: #f44336;
        }

        .stat-card.success .stat-icon,
        .stat-card.success .stat-value {
            color: #4caf50;
        }

        .dashboard-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .dashboard-section h3 {
            color: var(--pink-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--pink-light);
        }

        .recent-orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-orders-table th,
        .recent-orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .recent-orders-table th {
            background-color: var(--pink-light);
            color: var(--pink-dark);
            font-weight: bold;
        }

        .recent-orders-table tr:hover {
            background-color: #fff5f8;
        }

        .status-badge {
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

        .top-products-list {
            list-style: none;
            padding: 0;
        }

        .top-products-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fff5f8;
            border-radius: 8px;
            border-left: 4px solid var(--pink-dark);
        }

        .product-name {
            font-weight: 600;
            color: #333;
        }

        .product-sales {
            color: var(--pink-dark);
            font-weight: bold;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--pink-dark), var(--pink-medium));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 105, 180, 0.3);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.4);
        }

        .action-btn i {
            font-size: 1.5em;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--pink-dark), var(--pink-medium));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(255, 105, 180, 0.3);
        }

        .welcome-banner h1 {
            color: white;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2><i class="fas fa-crown"></i> Admin Panel</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                    <li><a href="view_orders.php"><i class="fas fa-shopping-cart"></i> View Orders</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="admin-main-content">
            <div class="welcome-banner">
                <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening with your store today.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <i class="fas fa-box stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>

                <div class="stat-card success">
                    <i class="fas fa-shopping-cart stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card warning">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>

                <div class="stat-card success">
                    <i class="fas fa-peso-sign stat-icon"></i>
                    <div class="stat-value">₱<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>

                <div class="stat-card warning">
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>

                <div class="stat-card danger">
                    <i class="fas fa-times-circle stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['out_of_stock']; ?></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="manage_products.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Product</span>
                </a>
                <a href="view_orders.php" class="action-btn">
                    <i class="fas fa-list-alt"></i>
                    <span>View All Orders</span>
                </a>
                <a href="manage_products.php" class="action-btn">
                    <i class="fas fa-inventory"></i>
                    <span>Manage Inventory</span>
                </a>
            </div>

            <!-- Recent Orders -->
            <div class="dashboard-section">
                <h3><i class="fas fa-receipt"></i> Recent Orders</h3>
                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                    <table class="recent-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                    <td><strong style="color: var(--pink-dark);">₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date("M j, Y", strtotime($order['order_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-text); padding: 20px;">No orders yet.</p>
                <?php endif; ?>
            </div>

            <!-- Top Selling Products -->
            <div class="dashboard-section">
                <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
                <?php if ($top_products && $top_products->num_rows > 0): ?>
                    <ul class="top-products-list">
                        <?php while($product = $top_products->fetch_assoc()): ?>
                            <li>
                                <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                <span class="product-sales"><?php echo $product['total_sold']; ?> sold</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-text); padding: 20px;">No sales data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>