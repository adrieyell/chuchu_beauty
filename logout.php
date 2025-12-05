<?php
session_start();

// Destroy all session data
session_destroy();

// Redirect back to the login page
header("Location: login.php");
exit();
?>
