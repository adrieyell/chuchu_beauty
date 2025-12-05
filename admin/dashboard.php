<?php
session_start();
include('../includes/db_config.php');

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    // If not admin, redirect them to the login page
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Basic Admin styles (can be moved to a separate admin.css later) */
        .admin-sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            padding: 20px;
            min-height: 100vh;
        }
        .admin-sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 0;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .admin-sidebar a:hover, .admin-sidebar .active {
            background-color: #ff69b4;
        }
        .admin-layout { display: flex; }
        .admin-content { flex-grow: 1; padding: 40px; }
        .logout-link { color: #f7e6ea; margin-top: 20px; }
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
        <h1>Welcome to the Admin Dashboard</h1>
        <p>Use the sidebar to manage products and view customer orders.</p>
    </div>
</body>
</html>