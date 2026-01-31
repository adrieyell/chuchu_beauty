<?php
// Make sure session is started before this file is included
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']);
$userName = $isLoggedIn ? ($_SESSION['user_fullname'] ?? 'User') : 'Guest';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isStaff = isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
?>

<header>
    <div class="logo">
        <h1><a href="index.php" style="color: white; text-decoration: none;">
            <i class="fas fa-sun"></i> Sol Beauty
        </a></h1>
    </div>
    <nav class="nav-icons">
        <?php if ($isLoggedIn): ?>
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php" title="Admin Dashboard"><i class="fas fa-user-shield"></i></a>
            <?php elseif ($isStaff): ?>
                <a href="staff/dashboard.php" title="Staff Dashboard"><i class="fas fa-user-tie"></i></a>
            <?php else: ?>
                <a href="index.php" title="Products"><i class="fas fa-home"></i></a>
                <a href="cart.php" title="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
            <?php endif; ?>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="index.php" title="Products"><i class="fas fa-home"></i></a>
            <a href="login.php" title="Login"><i class="fas fa-user"></i></a>
        <?php endif; ?>
    </nav>
</header>