<?php
// Save this as test_connection.php in your root directory
// Access it via: localhost/chuchu_beauty/test_connection.php

header('Content-Type: application/json');

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if config file exists
if (file_exists('includes/db_config.php')) {
    echo "<p style='color: green;'>✓ db_config.php found</p>";
    require_once('includes/db_config.php');
} elseif (file_exists('includes/db.php')) {
    echo "<p style='color: green;'>✓ db.php found</p>";
    require_once('includes/db.php');
} else {
    echo "<p style='color: red;'>✗ No database config file found!</p>";
    exit;
}

// Test 2: Check connection
if (isset($conn) && $conn->ping()) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Test 3: Check products table
$test_query = "DESCRIBE products";
$result = $conn->query($test_query);
if ($result) {
    echo "<p style='color: green;'>✓ Products table exists</p>";
    echo "<h3>Products Table Structure:</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Field']} ({$row['Type']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Products table not found</p>";
}

// Test 4: Check product_variants table
$test_query2 = "DESCRIBE product_variants";
$result2 = $conn->query($test_query2);
if ($result2) {
    echo "<p style='color: green;'>✓ Product_variants table exists</p>";
    echo "<h3>Product_variants Table Structure:</h3><ul>";
    while ($row = $result2->fetch_assoc()) {
        echo "<li>{$row['Field']} ({$row['Type']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠ Product_variants table not found (optional)</p>";
}

// Test 5: Count products
$count_query = "SELECT COUNT(*) as total FROM products";
$count_result = $conn->query($count_query);
if ($count_result) {
    $count = $count_result->fetch_assoc();
    echo "<p style='color: green;'>✓ Total products in database: {$count['total']}</p>";
}

// Test 6: Sample product fetch
$sample_query = "SELECT product_id, name FROM products LIMIT 1";
$sample_result = $conn->query($sample_query);
if ($sample_result && $sample_result->num_rows > 0) {
    $sample = $sample_result->fetch_assoc();
    echo "<p style='color: green;'>✓ Sample product: ID {$sample['product_id']} - {$sample['name']}</p>";
    
    // Test fetching this specific product
    echo "<h3>Testing fetch_product_details.php:</h3>";
    echo "<a href='fetch_product_details.php?id={$sample['product_id']}' target='_blank'>Click to test product {$sample['product_id']}</a>";
}

$conn->close();
?>