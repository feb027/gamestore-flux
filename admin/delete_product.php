<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get product ID
$product_id = $_POST['product_id'] ?? 0;

$conn = getDbConnection();

try {
    // Get product image URL before deletion
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    
    if ($stmt->execute()) {
        // Delete product image if exists
        if ($product && $product['image_url']) {
            $image_path = '../' . $product['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error deleting product');
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 