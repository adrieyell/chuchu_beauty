<?php
session_start();
include('includes/db_config.php');

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch order details for confirmation display
$order_details = null;
if ($order_id > 0) {
    $sql = "SELECT o.order_id, o.total_amount, o.order_date, o.payment_method, u.fullname 
            FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChuChu Beauty | Order Confirmed</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .confirmation-box {
            background-color: #fff;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(255, 105, 180, 0.15);
            max-width: 500px;
            text-align: center;
            margin: 50px auto;
        }
        .confirmation-box h1 {
            color: #4CAF50; /* Green success color */
            margin-bottom: 20px;
        }
        .confirmation-box .order-details {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            border: 1px dashed #ffafc9;
            border-radius: 10px;
        }
        .confirmation-box .order-details p {
            margin: 5px 0;
            font-size: 1.1em;
        }
    </style>
</head>
<body class="center-body">
    <div class="confirmation-box">
        <?php if ($order_details): ?>
            <i class="fas fa-check-circle" style="font-size: 4em; color: #4CAF50; margin-bottom: 20px;"></i>
            <h1>Order Successful!</h1>
            <p>Thank you, **<?php echo htmlspecialchars($order_details['fullname']); ?>**! Your order has been placed.</p>
            
            <div class="order-details">
                <p><strong>Order ID:</strong> #<?php echo $order_details['order_id']; ?></p>
                <p><strong>Total Amount:</strong> ₱<?php echo number_format($order_details['total_amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo $order_details['payment_method']; ?></p>
                <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($order_details['order_date'])); ?></p>
            </div>
            
            <a href="index.php" class="btn-pink">Continue Shopping</a>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle" style="font-size: 4em; color: #ff69b4; margin-bottom: 20px;"></i>
            <h1>Order Not Found</h1>
            <p>There was an issue processing your request or the order ID is invalid.</p>
            <a href="cart.php" class="btn-pink">Go to Cart</a>
        <?php endif; ?>
    </div>
</body>
</html>