<?php
// includes/email_config.php
// Email configuration using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// FIXED: Go up one directory level to find vendor folder
require __DIR__ . '/../vendor/autoload.php';

function sendOrderEmail($to_email, $subject, $body, $customer_name = 'Valued Customer') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'solbeautymakeup116@gmail.com'; 
        $mail->Password   = 'dhuv gpra ssvi ylgd'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('solbeautymakeup116@gmail.com', 'Sol Beauty');
        $mail->addAddress($to_email, $customer_name);
        $mail->addReplyTo('support@solbeauty.com', 'Sol Beauty Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getOrderConfirmationEmailTemplate($order_data) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #FF1493, #FF69B4); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
            .order-details { background: #fff5f8; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-details h3 { color: #FF1493; margin-top: 0; }
            .item-list { list-style: none; padding: 0; }
            .item-list li { padding: 10px 0; border-bottom: 1px solid #eee; }
            .total { font-size: 20px; font-weight: bold; color: #FF1493; margin-top: 15px; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; padding: 12px 30px; background: #FF1493; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>☀️ Sol Beauty</h1>
                <p>Where every shade shines</p>
            </div>
            <div class="content">
                <h2>Order Confirmation</h2>
                <p>Dear ' . htmlspecialchars($order_data['customer_name']) . ',</p>
                <p>Thank you for your order! We\'re excited to get your beauty products to you.</p>
                
                <div class="order-details">
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #' . $order_data['order_id'] . '</p>
                    <p><strong>Order Date:</strong> ' . date('F j, Y', strtotime($order_data['order_date'])) . '</p>
                    <p><strong>Order Status:</strong> ' . $order_data['status'] . '</p>
                    
                    <h4>Items Ordered:</h4>
                    <ul class="item-list">
                        ' . $order_data['items_html'] . '
                    </ul>
                    
                    <p class="total">Total: ₱' . number_format($order_data['total'], 2) . '</p>
                    
                    <h4>Shipping Information:</h4>
                    <p>' . nl2br(htmlspecialchars($order_data['shipping_address'])) . '</p>
                    
                    <h4>Payment Method:</h4>
                    <p>' . htmlspecialchars($order_data['payment_method']) . '</p>
                </div>
                
                <p>We\'ll send you another email when your order ships.</p>
                <p>If you have any questions, please don\'t hesitate to contact us.</p>
            </div>
            <div class="footer">
                <p>© 2025 Sol Beauty. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

function getOrderStatusUpdateEmailTemplate($order_data, $new_status) {
    $status_messages = [
        'Pending' => 'Your order has been received and is awaiting processing.',
        'Processing' => 'Great news! We\'re preparing your order for shipment.',
        'Shipped' => 'Your order is on its way! You should receive it within 3-5 business days.',
        'Delivered' => 'Your order has been delivered! We hope you love your new beauty products.',
        'Cancelled' => 'Your order has been cancelled. If you have questions, please contact us.'
    ];
    
    $status_icons = [
        'Pending' => '⏳',
        'Processing' => '📦',
        'Shipped' => '🚚',
        'Delivered' => '✅',
        'Cancelled' => '❌'
    ];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #FF1493, #FF69B4); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
            .status-update { background: #fff5f8; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
            .status-icon { font-size: 60px; margin-bottom: 10px; }
            .status-text { font-size: 24px; font-weight: bold; color: #FF1493; }
            .order-summary { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>☀️ Sol Beauty</h1>
                <p>Order Status Update</p>
            </div>
            <div class="content">
                <h2>Hello ' . htmlspecialchars($order_data['customer_name']) . ',</h2>
                
                <div class="status-update">
                    <div class="status-icon">' . $status_icons[$new_status] . '</div>
                    <div class="status-text">' . $new_status . '</div>
                    <p>' . $status_messages[$new_status] . '</p>
                </div>
                
                <div class="order-summary">
                    <p><strong>Order ID:</strong> #' . $order_data['order_id'] . '</p>
                    <p><strong>Order Date:</strong> ' . date('F j, Y', strtotime($order_data['order_date'])) . '</p>
                    <p><strong>Total Amount:</strong> ₱' . number_format($order_data['total'], 2) . '</p>
                </div>
                
                <p>Thank you for choosing Sol Beauty!</p>
            </div>
            <div class="footer">
                <p>© 2025 Sol Beauty. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}
?>