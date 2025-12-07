<?php
session_start();
include('includes/db_config.php');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']);

// If not logged in, redirect to login
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$cart_subtotal = 0;

// Fetch cart items with variant information
$cart_query = "SELECT c.cart_id, c.product_id, c.quantity, c.variant_id,
               p.name, p.price, p.image_url, p.stock_quantity,
               pv.shade_name, pv.shade_color, pv.stock_quantity as variant_stock
               FROM cart c 
               JOIN products p ON c.product_id = p.product_id 
               LEFT JOIN product_variants pv ON c.variant_id = pv.variant_id
               WHERE c.user_id = $user_id";

$cart_result = $conn->query($cart_query);

if ($cart_result && $cart_result->num_rows > 0) {
    while ($row = $cart_result->fetch_assoc()) {
        // Use variant stock if variant exists, otherwise use product stock
        $available_stock = !empty($row['variant_id']) ? $row['variant_stock'] : $row['stock_quantity'];
        
        $cart_items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => $row['product_id'],
            'variant_id' => $row['variant_id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'image_url' => $row['image_url'],
            'stock_quantity' => $available_stock,
            'shade_name' => $row['shade_name'],
            'shade_color' => $row['shade_color']
        ];
        $cart_subtotal += $row['price'] * $row['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChuChu Beauty | Shopping Cart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .cart-error-message {
            background-color: #f7e6e6;
            color: #a72d2d;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .cart-success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .shade-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
            padding: 5px 10px;
            background-color: #fff5f8;
            border-radius: 8px;
            font-size: 0.85em;
        }
        .shade-color-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <main class="cart-main-container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="cart-error-message">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="cart-success-message">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <div id="shopping-cart-view">
            <h2><i class="fas fa-shopping-bag"></i> Shopping Cart</h2>
            <div class="cart-layout">
                <div class="cart-items-list">
                    
                    <?php if (count($cart_items) > 0): ?>
                        <?php foreach ($cart_items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $image = !empty($item['image_url']) ? $item['image_url'] : 'assets/images/placeholder.jpg';
                        ?>
                        <div class="cart-item">
                            <div class="item-details">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    
                                    <?php if (!empty($item['shade_name'])): ?>
                                        <div class="shade-indicator">
                                            <div class="shade-color-dot" style="background-color: <?php echo htmlspecialchars($item['shade_color']); ?>;"></div>
                                            <span><strong>Shade:</strong> <?php echo htmlspecialchars($item['shade_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="qty-box small">
                                        <button class="qty-minus" onclick="updateCart(<?php echo $item['cart_id']; ?>, -1)">-</button>
                                        <input type="number" value="<?php echo $item['quantity']; ?>" min="1" readonly>
                                        <button class="qty-plus" onclick="updateCart(<?php echo $item['cart_id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="item-price">
                                <p class="total-price">₱<?php echo number_format($item_total, 2); ?></p>
                                <p class="unit-price">₱<?php echo number_format($item['price'], 2); ?> / item</p>
                                <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>" class="delete-item-btn" onclick="return confirm('Remove this item from cart?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background-color: white; border-radius: 15px;">
                            <i class="fas fa-shopping-cart" style="font-size: 4em; color: var(--pink-light); margin-bottom: 20px;"></i>
                            <h3>Your cart is empty</h3>
                            <p style="color: var(--gray-text); margin: 10px 0 20px 0;">Start shopping now and add items to your cart!</p>
                            <a href="index.php" class="btn-pink" style="display: inline-block; width: auto; padding: 12px 30px;">
                                Continue Shopping
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="order-summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-line"><span>Subtotal</span><span>₱<?php echo number_format($cart_subtotal, 2); ?></span></div>
                    <div class="summary-line"><span>Shipping</span><span class="free">FREE</span></div>
                    <p class="shipping-note">Free shipping on all orders!</p>
                    <hr>
                    <div class="summary-line total"><span>Total</span><span>₱<?php echo number_format($cart_subtotal, 2); ?></span></div>
                    
                    <button class="btn-pink full-width-btn" onclick="showCheckoutView()" <?php echo count($cart_items) == 0 ? 'disabled' : ''; ?>>
                        Proceed to Checkout
                    </button>
                    <button class="btn-gray full-width-btn" onclick="window.location.href='index.php'">
                        Continue Shopping
                    </button>
                </div>
            </div>
        </div>

        <div id="checkout-view" style="display: none;">
            <h2><i class="fas fa-money-check-alt"></i> Checkout</h2>
            <form action="place_order.php" method="POST" class="checkout-layout">
                <div class="checkout-forms">
                    <section class="shipping-info-section">
                        <h3>Shipping Information</h3>
                        <label for="checkout_fullname">Full Name</label>
                        <input type="text" id="checkout_fullname" name="fullname" placeholder="Full Name" required value="<?php echo htmlspecialchars($_SESSION['user_fullname'] ?? ''); ?>">

                        <label for="checkout_phone">Phone Number</label>
                        <input type="tel" id="checkout_phone" name="phone_number" placeholder="Phone Number" required value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>">

                        <label for="checkout_address">Shipping Address</label>
                        <textarea id="checkout_address" name="shipping_address" rows="3" placeholder="Street, Barangay, City, Province" required><?php echo htmlspecialchars($_SESSION['user_address'] ?? ''); ?></textarea>
                    </section>

                    <section class="payment-method-section">
                        <h3>Payment Method</h3>
                        <div class="payment-options">
                            <label>
                                <input type="radio" name="payment_method" value="GCash" required checked> 
                                GCash
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="COD" required> 
                                Cash on Delivery (COD)
                            </label>
                        </div>
                    </section>
                </div>

                <div class="order-summary-card checkout-summary">
                    <h3>Order Summary</h3>
                    <ul class="checkout-item-list">
                        <?php foreach ($cart_items as $item): ?>
                            <li>
                                <span>
                                    <?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?>
                                    <?php if (!empty($item['shade_name'])): ?>
                                        <br><small style="color: var(--gray-text);">Shade: <?php echo htmlspecialchars($item['shade_name']); ?></small>
                                    <?php endif; ?>
                                </span>
                                <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="summary-line"><span>Subtotal</span><span>₱<?php echo number_format($cart_subtotal, 2); ?></span></div>
                    <div class="summary-line"><span>Shipping</span><span class="free">FREE</span></div>
                    <hr>
                    <div class="summary-line total"><span>Total</span><span>₱<?php echo number_format($cart_subtotal, 2); ?></span></div>
                    
                    <button type="submit" class="btn-pink full-width-btn">Place Order</button>
                    <button type="button" class="btn-gray full-width-btn" onclick="showCartView()">Back to Cart</button>
                </div>
            </form>
        </div>

    </main>
    <script src="assets/js/script.js"></script>
    <script>
        function updateCart(cartId, change) {
            window.location.href = `update_cart.php?cart_id=${cartId}&change=${change}`;
        }

        function showCheckoutView() {
            document.getElementById('shopping-cart-view').style.display = 'none';
            document.getElementById('checkout-view').style.display = 'flex';
        }
        
        function showCartView() {
            document.getElementById('checkout-view').style.display = 'none';
            document.getElementById('shopping-cart-view').style.display = 'block';
        }
    </script>
</body>
</html>