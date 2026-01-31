<?php
session_start();
include('../includes/db_config.php');

// Check if the user is logged in and is staff
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Fetch dashboard statistics
$stats = [];

// Total Products (view only for staff)
$products_query = "SELECT COUNT(*) as total FROM products";
$products_result = $conn->query($products_query);
$stats['total_products'] = $products_result->fetch_assoc()['total'];

// Low Stock Products
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

// Active Orders (Pending, Processing, Shipped)
$active_query = "SELECT COUNT(*) as total FROM orders WHERE order_status IN ('Pending', 'Processing', 'Shipped')";
$active_result = $conn->query($active_query);
$stats['active_orders'] = $active_result->fetch_assoc()['total'];

// Pending Orders (staff's main responsibility)
$pending_query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'";
$pending_result = $conn->query($pending_query);
$stats['pending_orders'] = $pending_result->fetch_assoc()['total'];

// Processing Orders
$processing_query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'Processing'";
$processing_result = $conn->query($processing_query);
$stats['processing_orders'] = $processing_result->fetch_assoc()['total'];

// Finished Orders (Delivered + Cancelled)
$finished_query = "SELECT COUNT(*) as total FROM orders WHERE order_status IN ('Delivered', 'Cancelled')";
$finished_result = $conn->query($finished_query);
$stats['finished_orders'] = $finished_result->fetch_assoc()['total'];

// Today's Orders
$today_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = CURDATE()";
$today_result = $conn->query($today_query);
$stats['today_orders'] = $today_result->fetch_assoc()['total'];

// Yesterday's Orders (for comparison)
$yesterday_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$yesterday_result = $conn->query($yesterday_query);
$stats['yesterday_orders'] = $yesterday_result->fetch_assoc()['total'];

// Weekly Order Trend (Last 7 days)
$weekly_trend_query = "SELECT 
    DATE_FORMAT(order_date, '%a') as day_name,
    DATE(order_date) as order_day,
    COUNT(*) as order_count
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY order_date ASC";
$weekly_trend_result = $conn->query($weekly_trend_query);
$weekly_trend_data = [];
while ($row = $weekly_trend_result->fetch_assoc()) {
    $weekly_trend_data[] = $row;
}

// Order Status Distribution
$status_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$status_result = $conn->query($status_query);
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['order_status']] = $row['count'];
}

// Category Performance (Staff can see what's selling to help with customer inquiries)
$category_query = "SELECT p.category, 
    SUM(oi.quantity) as items_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    GROUP BY p.category
    ORDER BY items_sold DESC";
$category_result = $conn->query($category_query);
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[] = $row;
}

// Top Selling Products (Staff should know what's popular)
$top_products_query = "SELECT p.name, p.category, SUM(oi.quantity) as total_sold 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id 
                       GROUP BY oi.product_id 
                       ORDER BY total_sold DESC LIMIT 5";
$top_products = $conn->query($top_products_query);

// Recent Orders (last 10 - only active orders)
$recent_orders_query = "SELECT o.order_id, u.fullname, o.total_amount, o.order_date, o.order_status 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        WHERE o.order_status IN ('Pending', 'Processing', 'Shipped')
                        ORDER BY o.order_date DESC LIMIT 10";
$recent_orders = $conn->query($recent_orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff | Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stat-comparison {
            font-size: 0.85em;
            margin-top: 5px;
            position: relative;
            z-index: 1;
        }

        .stat-comparison.up {
            color: #4caf50;
        }

        .stat-comparison.down {
            color: #f44336;
        }

        .stat-comparison.same {
            color: #757575;
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

        .stat-card.info .stat-icon,
        .stat-card.info .stat-value {
            color: #0dcaf0;
        }

        .stat-card.completed .stat-icon,
        .stat-card.completed .stat-value {
            color: #198754;
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

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .chart-container h3 {
            color: var(--pink-dark);
            margin-bottom: 20px;
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
        .status-Cancelled { background-color: #dc3545; }

        .top-products-list {
            list-style: none;
            padding: 0;
        }

        .top-products-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #fff5f8;
            border-radius: 8px;
            border-left: 4px solid var(--pink-dark);
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: #333;
            font-size: 1.05em;
        }

        .product-category {
            color: var(--gray-text);
            font-size: 0.9em;
            margin-top: 3px;
        }

        .product-sales {
            color: var(--pink-dark);
            font-weight: bold;
            font-size: 1.2em;
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

        .action-btn.success {
            background: linear-gradient(135deg, #198754, #20c997);
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
        }

        .action-btn.success:hover {
            box-shadow: 0 6px 20px rgba(25, 135, 84, 0.4);
        }

        .welcome-banner {
            background: linear-gradient(135deg, #0dcaf0, #20c997);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(13, 202, 240, 0.3);
        }

        .welcome-banner h1 {
            color: white;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1em;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .info-box i {
            color: #0dcaf0;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2><i class="fas fa-user-tie"></i> Staff Panel</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="view_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="admin-main-content">
            <div class="welcome-banner">
                <h1><i class="fas fa-chart-line"></i> Staff Dashboard</h1>
                <p>Welcome! Here's your order management overview for Sol Beauty.</p>
            </div>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Staff Permissions:</strong> You can view all orders and update order statuses. Product management is restricted to admin only.
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="stat-card warning">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>

                <div class="stat-card info">
                    <i class="fas fa-cog stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['processing_orders']; ?></div>
                    <div class="stat-label">Processing Orders</div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-tasks stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['active_orders']; ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>

                <div class="stat-card completed">
                    <i class="fas fa-check-circle stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['finished_orders']; ?></div>
                    <div class="stat-label">Finished Orders</div>
                </div>

                <div class="stat-card success">
                    <i class="fas fa-calendar-day stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['today_orders']; ?></div>
                    <div class="stat-label">Today's Orders</div>
                    <?php 
                        $diff = $stats['today_orders'] - $stats['yesterday_orders'];
                        if ($diff > 0) {
                            echo '<div class="stat-comparison up"><i class="fas fa-arrow-up"></i> +' . $diff . ' from yesterday</div>';
                        } elseif ($diff < 0) {
                            echo '<div class="stat-comparison down"><i class="fas fa-arrow-down"></i> ' . $diff . ' from yesterday</div>';
                        } else {
                            echo '<div class="stat-comparison same"><i class="fas fa-minus"></i> Same as yesterday</div>';
                        }
                    ?>
                </div>

                <div class="stat-card">
                    <i class="fas fa-shopping-cart stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-box stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>

                <div class="stat-card warning">
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>

            <!-- Analytics Charts -->
            <div class="charts-grid">
                <!-- Weekly Order Trend -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> Weekly Order Trend</h3>
                    <canvas id="weeklyTrendChart"></canvas>
                </div>

                <!-- Order Status Distribution -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Order Status Distribution</h3>
                    <canvas id="orderStatusChart"></canvas>
                </div>

                <!-- Category Performance -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> Popular Categories</h3>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="view_orders.php?filter=active" class="action-btn">
                    <i class="fas fa-clock"></i>
                    <span>Active Orders (<?php echo $stats['active_orders']; ?>)</span>
                </a>
                <a href="view_orders.php?filter=finished" class="action-btn success">
                    <i class="fas fa-check-circle"></i>
                    <span>Finished Orders (<?php echo $stats['finished_orders']; ?>)</span>
                </a>
            </div>

            <!-- Recent Active Orders -->
            <div class="dashboard-section">
                <h3><i class="fas fa-receipt"></i> Recent Active Orders</h3>
                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                    <table class="recent-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                    <td><strong style="color: var(--pink-dark);">₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date("M j, Y g:i A", strtotime($order['order_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                                    <td>
                                        <a href="view_orders.php?filter=active" style="color: var(--pink-dark); text-decoration: none;">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-text); padding: 20px;">No active orders at the moment.</p>
                <?php endif; ?>
            </div>

            <!-- Top Selling Products -->
            <div class="dashboard-section">
                <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
                <p style="color: var(--gray-text); margin-bottom: 15px; font-size: 0.95em;">
                    <i class="fas fa-info-circle"></i> Know what customers are buying most - great for recommendations!
                </p>
                <?php if ($top_products && $top_products->num_rows > 0): ?>
                    <ul class="top-products-list">
                        <?php $rank = 1; while($product = $top_products->fetch_assoc()): ?>
                            <li>
                                <div class="product-info">
                                    <div class="product-name">
                                        <i class="fas fa-medal" style="color: <?php 
                                            echo $rank == 1 ? '#FFD700' : ($rank == 2 ? '#C0C0C0' : ($rank == 3 ? '#CD7F32' : '#ddd')); 
                                        ?>"></i>
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                </div>
                                <span class="product-sales"><?php echo $product['total_sold']; ?> sold</span>
                            </li>
                        <?php $rank++; endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-text); padding: 20px;">No sales data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Weekly Order Trend Chart
        const weeklyTrendCtx = document.getElementById('weeklyTrendChart').getContext('2d');
        const weeklyTrendChart = new Chart(weeklyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($weekly_trend_data, 'day_name')); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($weekly_trend_data, 'order_count')); ?>,
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0dcaf0',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($status_data)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_data)); ?>,
                    backgroundColor: ['#ffc107', '#0dcaf0', '#20c997', '#198754', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Category Performance Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($category_data, 'category')); ?>,
                datasets: [{
                    label: 'Items Sold',
                    data: <?php echo json_encode(array_column($category_data, 'items_sold')); ?>,
                    backgroundColor: '#20c997',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>