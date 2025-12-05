<?php
session_start();

// Check if there's a successful order
if (!isset($_SESSION['order_success']) || !$_SESSION['order_success']) {
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['order_id'] ?? 'N/A';
$order_total = $_SESSION['order_total'] ?? 0;

// Clear the order session data
unset($_SESSION['order_success']);
unset($_SESSION['order_id']);
unset($_SESSION['order_total']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful | ChuChu Beauty</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background-color: white;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }
        .success-icon {
            font-size: 5em;
            color: #28a745;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .success-container h1 {
            color: var(--pink-dark);
            margin-bottom: 15px;
        }
        .order-info {
            background-color: var(--pink-light);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .order-info p {
            margin: 10px 0;
            font-size: 1.1em;
        }
        .order-info strong {
            color: var(--pink-dark);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Order Placed Successfully!</h1>
        <p style="color: var(--gray-text); margin-bottom: 30px;">
            Thank you for your purchase! Your order has been confirmed.
        </p>
        
        <div class="order-info">
            <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
            <p><strong>Total Amount:</strong> ₱<?php echo number_format($order_total, 2); ?></p>
            <p style="margin-top: 15px; font-size: 0.95em; color: var(--gray-text);">
                <i class="fas fa-info-circle"></i> 
                We'll process your order shortly. Check your email for order confirmation.
            </p>
        </div>
        
        <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
            <a href="index.php" class="btn-pink" style="width: auto; padding: 12px 30px;">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    </div>
    
    <script>
        // Optional: Confetti effect or celebration animation
        console.log('Order placed successfully! 🎉');
    </script>
</body>
</html>