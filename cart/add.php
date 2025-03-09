<?php
require_once '../includes/config.php';

// Require login for adding to cart
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 1);
    $user_id = getCurrentUserId();

    if ($product_id) {
        // Get product from database
        $conn = getDbConnection();
        
        // Check if product exists
        $stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Check if item already exists in cart
            $stmt = $conn->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?');
            $stmt->bind_param('ii', $user_id, $product_id);
            $stmt->execute();
            $cart_result = $stmt->get_result();

            if ($cart_result->num_rows > 0) {
                // Update existing cart item
                $stmt = $conn->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?');
                $stmt->bind_param('iii', $quantity, $user_id, $product_id);
            } else {
                // Insert new cart item
                $stmt = $conn->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
                $stmt->bind_param('iii', $user_id, $product_id, $quantity);
            }

            if ($stmt->execute()) {
                setFlashMessage('success', 'Game added to cart successfully!');
            } else {
                setFlashMessage('danger', 'Failed to add game to cart. Please try again.');
            }
        } else {
            setFlashMessage('danger', 'Product not found.');
        }
        $conn->close();
    }
}

// Redirect back to previous page or products
$referer = $_SERVER['HTTP_REFERER'] ?? null;
if ($referer && strpos($referer, BASE_URL) === 0) {
    header("Location: $referer");
} else {
    redirect('/pages/products.php');
}
exit();
