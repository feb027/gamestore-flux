<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get product ID
$product_id = $_GET['id'] ?? 0;

$conn = getDbConnection();

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Format release date for HTML date input
$product['release_date'] = date('Y-m-d', strtotime($product['release_date']));

// Return product details as JSON
header('Content-Type: application/json');
echo json_encode($product); 