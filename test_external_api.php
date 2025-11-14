<?php
/**
 * External Products API - Test Page
 * Quick verification that everything is working
 */

session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>External Products API - Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test { margin: 20px 0; padding: 15px; border-left: 4px solid #667eea; }
        .success { border-left-color: #27ae60; background: #ecfdf5; }
        .error { border-left-color: #e74c3c; background: #fee; }
        .info { border-left-color: #3498db; background: #e3f2fd; }
        h1 { color: #333; }
        pre { background: #f8f8f8; padding: 10px; overflow-x: auto; border-radius: 4px; }
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 External Products API - Test Suite</h1>";

// Test 1: Cache directory
echo "<div class='test info'>
    <strong>Test 1: Cache Directory</strong><br>";
if (is_dir('cache')) {
    echo "<span style='color: green;'>✓ Cache directory exists</span>";
} else {
    mkdir('cache', 0755, true);
    echo "<span style='color: green;'>✓ Cache directory created</span>";
}
echo "</div>";

// Test 2: DummyJSON API
echo "<div class='test'>";
echo "<strong>Test 2: DummyJSON API</strong><br>";
$url = "https://dummyjson.com/products?limit=3";
$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['products']) && count($data['products']) > 0) {
        echo "<span style='color: green;'>✓ DummyJSON API is working</span><br>";
        echo "Sample products:<br>";
        foreach (array_slice($data['products'], 0, 2) as $product) {
            echo "- " . $product['title'] . " (\$" . $product['price'] . ")<br>";
        }
    }
} else {
    echo "<span style='color: red;'>✗ DummyJSON API connection failed</span>";
}
echo "</div>";

// Test 3: Fake Store API
echo "<div class='test'>";
echo "<strong>Test 3: Fake Store API</strong><br>";
$url = "https://fakestoreapi.com/products?limit=3";
$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        echo "<span style='color: green;'>✓ Fake Store API is working</span><br>";
        echo "Sample products:<br>";
        foreach (array_slice($data, 0, 2) as $product) {
            echo "- " . $product['title'] . " (\$" . $product['price'] . ")<br>";
        }
    }
} else {
    echo "<span style='color: red;'>✗ Fake Store API connection failed</span>";
}
echo "</div>";

// Test 4: Platzi API
echo "<div class='test'>";
echo "<strong>Test 4: Platzi Fake Store API</strong><br>";
$url = "https://api.escuelajs.co/api/v1/products?limit=3";
$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        echo "<span style='color: green;'>✓ Platzi API is working</span><br>";
        echo "Sample products:<br>";
        foreach (array_slice($data, 0, 2) as $product) {
            echo "- " . $product['title'] . " (\$" . $product['price'] . ")<br>";
        }
    }
} else {
    echo "<span style='color: red;'>✗ Platzi API connection failed</span>";
}
echo "</div>";

// Test 5: Database Connection
echo "<div class='test'>";
echo "<strong>Test 5: Database Connection</strong><br>";
try {
    include 'components/connect.php';
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "<span style='color: green;'>✓ Database connection successful</span>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</span>";
}
echo "</div>";

echo "
    <div style='margin-top: 30px; text-align: center;'>
        <p><strong>All systems ready!</strong></p>
        <button onclick=\"location.href='external_products_display.php'\">Go to External Products</button>
        <button onclick=\"location.href='home.php'\" style='background: #666; margin-left: 10px;'\">Back to Home</button>
    </div>
    </div>
</body>
</html>";
?>
